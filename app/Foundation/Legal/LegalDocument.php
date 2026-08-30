<?php

namespace App\Foundation\Legal;

/**
 * Beschreibt eine exakt versionierte, lokal ausgelieferte rechtliche Dokumentvorlage.
 */
final readonly class LegalDocument
{
    public function __construct(
        public string $key,
        public string $version,
        public string $locale,
        public string $digest,
        public string $content,
    ) {}
}
