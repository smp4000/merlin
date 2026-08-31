<?php

namespace App\Modules\Stations\Infrastructure;

use App\Modules\Stations\Application\Exceptions\StationSearchUnavailableException;
use App\Modules\Stations\Contracts\StationSearchProvider;
use App\Modules\Stations\Domain\StationDetails;
use App\Modules\Stations\Domain\StationSearchResponse;
use App\Modules\Stations\Domain\StationSearchResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Liest Standortstammdaten aus der optionalen Pilotsuche von benzinpreis-aktuell.de.
 *
 * Der Adapter ist die einzige Stelle, die die nicht garantierte HTML-Struktur kennt.
 * Preisfelder werden nicht geparst und können deshalb weder Fachlogik noch Datenbank,
 * Cache oder Audit erreichen. Struktur- und Netzwerkfehler fallen kontrolliert auf die
 * manuelle Stationsanlage zurück.
 */
final class BenzinpreisAktuellStationSearchProvider implements StationSearchProvider
{
    private const PROVIDER_KEY = 'benzinpreis_aktuell';

    public function __construct(private readonly StationSearchReferenceSigner $signer) {}

    /**
     * Sucht über die Website nach Stationen. Nicht native Radiuswerte werden transparent
     * über den nächstgrößeren unterstützten Umfang angefragt; bei 25 km wird auf die
     * bekannte 20-km-Grenze der Quelle hingewiesen.
     */
    public function search(string $postalCode, int $radius): StationSearchResponse
    {
        $this->ensureEnabled();
        $this->validateSearch($postalCode, $radius);

        $cacheKey = 'station-search:'.hash('sha256', self::PROVIDER_KEY.'|'.$postalCode.'|'.$radius);

        $cached = Cache::remember(
            $cacheKey,
            now()->addMinutes((int) config('merlin.station_search.cache_minutes', 15)),
            fn (): array => $this->serializeResponse($this->performSearch($postalCode, $radius)),
        );

        // Cachetreiber dürfen keine Fachobjekte rekonstruieren müssen. Alte Pilotwerte
        // oder unerwartete Typen werden verworfen und einmal frisch aufgebaut.
        if (! is_array($cached) || ! isset($cached['results']) || ! is_array($cached['results'])) {
            Cache::forget($cacheKey);
            $response = $this->performSearch($postalCode, $radius);
            Cache::put(
                $cacheKey,
                $this->serializeResponse($response),
                now()->addMinutes((int) config('merlin.station_search.cache_minutes', 15)),
            );

            return $response;
        }

        return $this->deserializeResponse($cached);
    }

    /**
     * Prüft Signatur, Provider und Detailseite erneut, bevor Werte ins Formular gelangen.
     */
    public function details(string $signedReference): StationDetails
    {
        $this->ensureEnabled();
        $payload = $this->signer->verify($signedReference);

        if ($payload['provider'] !== self::PROVIDER_KEY) {
            throw ValidationException::withMessages([
                'selectedReference' => __('stations.validation.reference_invalid'),
            ]);
        }

        $url = $this->baseUrl().'/preise-t'.$payload['hash'].'-'.$payload['slug'];

        try {
            $response = $this->client()->get($url);
        } catch (ConnectionException $exception) {
            Log::warning('Stationssuche: Detailseite nicht erreichbar.', ['provider' => self::PROVIDER_KEY]);
            throw new StationSearchUnavailableException(previous: $exception);
        }

        if (! $response->successful() || strlen($response->body()) > 2_000_000) {
            throw new StationSearchUnavailableException;
        }

        return $this->parseDetails($response->body(), $payload['external_id']);
    }

    private function performSearch(string $postalCode, int $radius): StationSearchResponse
    {
        try {
            $locationResponse = $this->client()->asForm()->post(
                $this->baseUrl().'/sqlite/slite.php',
                ['usearch' => $postalCode],
            );

            if (! $locationResponse->successful() || strlen($locationResponse->body()) > 50_000) {
                throw new StationSearchUnavailableException;
            }

            $locationSlug = $this->parseLocationSlug($locationResponse->body(), $postalCode);
            if ($locationSlug === null) {
                return new StationSearchResponse([]);
            }

            $upstreamRadius = match ($radius) {
                2 => 3,
                15, 25 => 20,
                default => $radius,
            };
            $url = $this->baseUrl().'/'.$locationSlug.'-aktuelle-e10preise?umkreis='.$upstreamRadius;
            $response = $this->client()->get($url);

            if (! $response->successful() || strlen($response->body()) > 2_000_000) {
                throw new StationSearchUnavailableException;
            }

            $results = $this->parseSearchResults($response->body(), $postalCode, $radius);
            $warning = match ($radius) {
                2, 15 => __('stations.search.radius_approximation_warning', ['radius' => $radius]),
                25 => __('stations.search.radius_limit_warning'),
                default => null,
            };

            return new StationSearchResponse($results, $warning);
        } catch (StationSearchUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Stationssuche: Providerantwort konnte nicht verarbeitet werden.', [
                'provider' => self::PROVIDER_KEY,
                'exception' => $exception::class,
            ]);

            throw new StationSearchUnavailableException(previous: $exception);
        }
    }

    /**
     * @return array{results: list<array{reference: string, name: string, street: string, city: string, is_open: bool|null}>, warning: string|null}
     */
    private function serializeResponse(StationSearchResponse $response): array
    {
        return [
            'results' => array_map(
                static fn (StationSearchResult $result): array => $result->toArray(),
                $response->results,
            ),
            'warning' => $response->warning,
        ];
    }

    /**
     * @param  array{results: array<int, array<string, mixed>>, warning?: mixed}  $cached
     */
    private function deserializeResponse(array $cached): StationSearchResponse
    {
        $results = [];

        foreach ($cached['results'] as $result) {
            if (! isset($result['reference'], $result['name'], $result['street'], $result['city'])) {
                continue;
            }

            $results[] = new StationSearchResult(
                (string) $result['reference'],
                (string) $result['name'],
                (string) $result['street'],
                (string) $result['city'],
                is_bool($result['is_open'] ?? null) ? $result['is_open'] : null,
            );
        }

        return new StationSearchResponse(
            $results,
            is_string($cached['warning'] ?? null) ? $cached['warning'] : null,
        );
    }

    private function parseLocationSlug(string $html, string $postalCode): ?string
    {
        if (! preg_match_all('/data-zipm="([^"]+)"[^>]*>([^<]+)<\/span>/iu', $html, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            $candidate = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (str_starts_with($candidate, $postalCode.'-') && preg_match('/^\d{5}-[a-z0-9-]+$/', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return list<StationSearchResult> */
    private function parseSearchResults(string $html, string $postalCode, int $radius): array
    {
        $results = [];
        $pattern = '/<div id="station-t([a-f0-9]+)-([^"]+)"[^>]*data-mid="([^"]+)"[^>]*>(.+?)(?=<div id="station-t|<div class="sxi"|$)/si';

        if (! preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($blocks as $block) {
            if (! preg_match('/<strong class="isstrong">([^<]+)<\/strong><br>\s*([^<]+)/si', $block[4], $nameMatch)) {
                continue;
            }

            $name = $this->clean($nameMatch[1]);
            $street = $this->clean($nameMatch[2]);
            if ($name === '' || $street === '') {
                continue;
            }

            $externalId = trim($block[3]);
            $hash = trim($block[1]);
            $slug = trim($block[2], '/');
            $city = preg_match('/Tankstelle\s+(.+)$/iu', $name, $cityMatch) ? trim($cityMatch[1]) : '';
            $reference = $this->signer->sign([
                'provider' => self::PROVIDER_KEY,
                'external_id' => $externalId,
                'hash' => $hash,
                'slug' => $slug,
                'postal_code' => $postalCode,
                'radius' => $radius,
            ]);

            $results[$externalId] = new StationSearchResult(
                $reference,
                $name,
                $street,
                $city,
                ! preg_match('/geschlossen/iu', $block[4]),
            );
        }

        $values = array_values($results);
        usort($values, fn (StationSearchResult $left, StationSearchResult $right): int => strcasecmp($left->name, $right->name));

        return $values;
    }

    private function parseDetails(string $html, string $externalId): StationDetails
    {
        $name = '';
        $street = '';
        $houseNumber = '';
        $postalCode = '';
        $city = '';
        $latitude = null;
        $longitude = null;

        if (preg_match('/<title>\s*([^|<]+)/iu', $html, $titleMatch)) {
            $name = preg_replace('/\s*(?:Spritpreise|Preise).*$/iu', '', $this->clean($titleMatch[1])) ?: '';
        }

        if (preg_match('/Wo finde ich die Tankstelle\?\s*<\/h2>\s*<p[^>]*>\s*(.+?)\s+(\d[\d\s\-\/a-z]*?)\s*<br>\s*(\d{5})\s+([^<]+)/iu', $html, $address)) {
            $street = $this->clean($address[1]);
            $houseNumber = $this->clean($address[2]);
            $postalCode = trim($address[3]);
            $city = $this->clean($address[4]);
        } elseif (preg_match('/daddr=([^&"\']+)/iu', $html, $mapMatch)) {
            [$street, $houseNumber, $postalCode, $city] = $this->parseMapAddress($mapMatch[1]);
        }

        if (preg_match('/property="place:location:latitude"\s+content="([\d.\-]+)"/iu', $html, $match)) {
            $latitude = round((float) $match[1], 7);
        }
        if (preg_match('/property="place:location:longitude"\s+content="([\d.\-]+)"/iu', $html, $match)) {
            $longitude = round((float) $match[1], 7);
        }

        if ($name === '' || $street === '' || $postalCode === '' || $city === '') {
            throw new StationSearchUnavailableException;
        }

        return new StationDetails(
            self::PROVIDER_KEY,
            $externalId,
            $name,
            $street,
            $houseNumber,
            $postalCode,
            rtrim($city, ':.,;'),
            $latitude,
            $longitude,
            hash('sha256', $name.'|'.$street.'|'.$houseNumber.'|'.$postalCode.'|'.$city),
        );
    }

    /** @return array{string, string, string, string} */
    private function parseMapAddress(string $rawAddress): array
    {
        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => rawurldecode(trim($part)),
            explode('+', $rawAddress),
        )));

        foreach ($parts as $index => $part) {
            if (! preg_match('/^\d{5}$/', $part)) {
                continue;
            }

            $streetValue = trim(implode(' ', array_slice($parts, 0, $index)));
            $streetValue = preg_replace('/^.*?Tankstelle\s+\S+\s+/iu', '', $streetValue) ?: $streetValue;
            $street = $streetValue;
            $houseNumber = '';
            if (preg_match('/^(.+?)\s+(\d[\d\s\-\/a-z]*)$/iu', $streetValue, $streetMatch)) {
                $street = trim($streetMatch[1]);
                $houseNumber = trim($streetMatch[2]);
            }

            return [$street, $houseNumber, $part, trim(implode(' ', array_slice($parts, $index + 1)))];
        }

        return ['', '', '', ''];
    }

    private function validateSearch(string $postalCode, int $radius): void
    {
        if (! preg_match('/^\d{5}$/', $postalCode)) {
            throw ValidationException::withMessages(['postalCode' => __('stations.validation.postal_code')]);
        }

        if (! in_array($radius, config('merlin.station_search.radii', []), true)) {
            throw ValidationException::withMessages(['radius' => __('stations.validation.radius')]);
        }
    }

    private function ensureEnabled(): void
    {
        if (! config('merlin.station_search.enabled')) {
            throw new StationSearchUnavailableException;
        }
    }

    private function baseUrl(): string
    {
        $baseUrl = rtrim((string) config('merlin.station_search.base_url'), '/');
        $host = parse_url($baseUrl, PHP_URL_HOST);

        if ($host !== config('merlin.station_search.allowed_host') || ! str_starts_with($baseUrl, 'https://')) {
            throw new StationSearchUnavailableException;
        }

        return $baseUrl;
    }

    private function client(): PendingRequest
    {
        return Http::timeout((int) config('merlin.station_search.timeout_seconds', 12))
            ->connectTimeout(5)
            ->withUserAgent('Merlin-StationSearch/0.1')
            ->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
            ->withoutRedirecting();
    }

    private function clean(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
