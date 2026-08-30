<?php

namespace App\Foundation\Legal;

use RuntimeException;

/**
 * Lädt rechtliche Hinweise aus unveränderlichen lokalen Quelldateien.
 *
 * Anzeige und Zustimmungsnachweis verwenden dadurch denselben kanonischen Inhalt. Eine
 * Produktionsumgebung verweigert Entwicklungsfassungen, damit sie nicht versehentlich
 * als juristisch freigegebene Dokumente protokolliert.
 */
final class LegalDocumentRepository
{
    /**
     * Liefert Version, SHA-256-Digest und Inhalt einer freigegebenen Vorlage.
     */
    public function get(string $key): LegalDocument
    {
        $configuration = config("merlin.registration.documents.{$key}");
        $version = (string) ($configuration['version'] ?? '');
        $locale = (string) ($configuration['locale'] ?? '');
        $path = (string) ($configuration['path'] ?? '');

        if ($version === '' || $locale === '' || $path === '' || ! is_file($path)) {
            throw new RuntimeException("Rechtliche Dokumentvorlage '{$key}' ist nicht konfiguriert.");
        }

        if (app()->environment('production')
            && (str_starts_with($version, 'development-')
                || str_contains(strtolower(basename($path)), 'development'))) {
            throw new RuntimeException("Entwicklungsfassung '{$key}' darf nicht produktiv verwendet werden.");
        }

        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            throw new RuntimeException("Rechtliche Dokumentvorlage '{$key}' ist leer oder nicht lesbar.");
        }

        return new LegalDocument($key, $version, $locale, hash('sha256', $content), $content);
    }
}
