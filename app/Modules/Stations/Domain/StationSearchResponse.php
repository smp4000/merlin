<?php

namespace App\Modules\Stations\Domain;

/**
 * Bündelt Treffer und eine mögliche Reichweitenwarnung der externen Pilotquelle.
 */
final readonly class StationSearchResponse
{
    /** @param list<StationSearchResult> $results */
    public function __construct(
        public array $results,
        public ?string $warning = null,
    ) {}
}
