<?php

namespace App\Modules\Stations\Infrastructure;

use Illuminate\Validation\ValidationException;
use JsonException;

/**
 * Signiert kurzlebige Suchtreffer, damit der Browser keine Providerkennung austauscht.
 */
final class StationSearchReferenceSigner
{
    /**
     * @param  array{provider: string, external_id: string, hash: string, slug: string, postal_code: string, radius: int}  $payload
     */
    public function sign(array $payload): string
    {
        $payload['expires_at'] = now()->addMinutes(20)->timestamp;
        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encoded, (string) config('app.key'));

        return $encoded.'.'.$signature;
    }

    /**
     * @return array{provider: string, external_id: string, hash: string, slug: string, postal_code: string, radius: int, expires_at: int}
     */
    public function verify(string $reference): array
    {
        [$encoded, $signature] = array_pad(explode('.', $reference, 2), 2, '');
        $expected = hash_hmac('sha256', $encoded, (string) config('app.key'));

        if ($encoded === '' || ! hash_equals($expected, $signature)) {
            throw $this->invalidReference();
        }

        try {
            $payload = json_decode($this->base64UrlDecode($encoded), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $this->invalidReference();
        }

        $required = ['provider', 'external_id', 'hash', 'slug', 'postal_code', 'radius', 'expires_at'];
        if (! is_array($payload) || array_diff($required, array_keys($payload)) !== []) {
            throw $this->invalidReference();
        }

        if ((int) $payload['expires_at'] < now()->timestamp) {
            throw ValidationException::withMessages([
                'selectedReference' => __('stations.validation.reference_expired'),
            ]);
        }

        /** @var array{provider: string, external_id: string, hash: string, slug: string, postal_code: string, radius: int, expires_at: int} $payload */
        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);

        if ($decoded === false) {
            throw $this->invalidReference();
        }

        return $decoded;
    }

    private function invalidReference(): ValidationException
    {
        return ValidationException::withMessages([
            'selectedReference' => __('stations.validation.reference_invalid'),
        ]);
    }
}
