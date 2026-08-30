<?php

namespace App\Modules\Banking\Application;

use App\Models\BankDirectoryEntry;
use App\Models\BankDirectorySource;
use App\Models\BankDirectoryVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lädt und versioniert das öffentliche Bankleitzahlenverzeichnis der Bundesbank.
 *
 * Die Quelle ist gegen einen festen HTTPS-Host geprüft. Fehlerhafte oder unerwartete
 * Dateien werden vor der Datenbanktransaktion abgewiesen, sodass die aktive Version
 * unverändert nutzbar bleibt.
 */
final class ImportBankDirectory
{
    /**
     * Importiert eine vollständige Version und aktiviert sie atomar.
     */
    public function handle(BankDirectorySource $source): BankDirectoryVersion
    {
        $this->assertSafeSource($source);

        $response = Http::timeout(20)
            ->connectTimeout(5)
            ->withoutRedirecting()
            ->get($source->url);

        if (! $response->successful()) {
            throw new RuntimeException("Bankverzeichnis konnte nicht geladen werden ({$response->status()}).");
        }

        $content = $response->body();

        if ($content === '' || strlen($content) > 10_000_000) {
            throw new RuntimeException('Bankverzeichnis ist leer oder unerwartet groß.');
        }

        $sha256 = hash('sha256', $content);
        $existing = BankDirectoryVersion::query()->where('sha256', $sha256)->first();

        if ($existing !== null) {
            $source->forceFill(['last_checked_at' => now()])->save();

            return $existing;
        }

        $rows = $this->parse($content);

        return DB::transaction(function () use ($source, $sha256, $rows): BankDirectoryVersion {
            $previous = BankDirectoryVersion::query()
                ->where('status', 'active')
                ->latest('activated_at')
                ->first();

            $previousHashes = $previous?->entries()
                ->get()
                ->mapWithKeys(fn (BankDirectoryEntry $entry): array => [
                    $entry->record_number => $this->entryHash($entry->getAttributes()),
                ])
                ->all() ?? [];
            $nextHashes = collect($rows)->mapWithKeys(fn (array $row): array => [
                $row['record_number'] => $this->entryHash($row),
            ])->all();

            BankDirectoryVersion::query()->where('status', 'active')->update(['status' => 'replaced']);

            $version = BankDirectoryVersion::query()->create([
                'bank_directory_source_id' => $source->getKey(),
                'sha256' => $sha256,
                'status' => 'active',
                'entry_count' => count($rows),
                'added_count' => count(array_diff_key($nextHashes, $previousHashes)),
                'changed_count' => collect(array_intersect_key($nextHashes, $previousHashes))
                    ->filter(fn (string $hash, string $key): bool => $previousHashes[$key] !== $hash)
                    ->count(),
                'deleted_count' => count(array_diff_key($previousHashes, $nextHashes)),
                'imported_at' => now(),
                'activated_at' => now(),
            ]);

            foreach (array_chunk($rows, 500) as $chunk) {
                $version->entries()->createMany($chunk);
            }

            $source->forceFill([
                'last_checked_at' => now(),
                'last_imported_at' => now(),
            ])->save();

            return $version;
        });
    }

    private function assertSafeSource(BankDirectorySource $source): void
    {
        $parts = parse_url($source->url);

        if (($parts['scheme'] ?? null) !== 'https'
            || mb_strtolower((string) ($parts['host'] ?? '')) !== mb_strtolower($source->allowed_host)
            || ! str_ends_with(mb_strtolower((string) ($parts['path'] ?? '')), '.csv')) {
            throw new RuntimeException('Nur die freigegebene HTTPS-CSV-Quelle der Bundesbank ist zulässig.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function parse(string $content): array
    {
        $utf8 = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $utf8);
        rewind($stream);
        $header = fgetcsv($stream, separator: ';');

        if (! is_array($header) || count($header) !== 13 || $header[0] !== 'Bankleitzahl') {
            throw new RuntimeException('Das Schema der Bundesbank-Datei ist unbekannt.');
        }

        $rows = [];

        while (($columns = fgetcsv($stream, separator: ';')) !== false) {
            if (count($columns) !== 13 || preg_match('/^\d{8}$/', $columns[0]) !== 1) {
                throw new RuntimeException('Ein Datensatz des Bankverzeichnisses ist ungültig.');
            }

            $rows[] = [
                'bank_code' => $columns[0],
                'leading_institution' => $columns[1],
                'name' => $columns[2],
                'postal_code' => $columns[3] ?: null,
                'city' => $columns[4] ?: null,
                'short_name' => $columns[5] ?: null,
                'pan' => $columns[6] ?: null,
                'bic' => $columns[7] ?: null,
                'account_check_method' => $columns[8] ?: null,
                'record_number' => $columns[9],
                'change_indicator' => $columns[10] ?: null,
                'deletion_announced' => $columns[11] === '1',
                'successor_bank_code' => $columns[12] !== '00000000' ? $columns[12] : null,
            ];
        }

        fclose($stream);

        if (count($rows) < 1000) {
            throw new RuntimeException('Das Bankverzeichnis enthält unerwartet wenige Datensätze.');
        }

        return $rows;
    }

    /** @param array<string, mixed> $row */
    private function entryHash(array $row): string
    {
        unset($row['id'], $row['bank_directory_version_id'], $row['created_at'], $row['updated_at']);
        ksort($row);

        return hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
    }
}
