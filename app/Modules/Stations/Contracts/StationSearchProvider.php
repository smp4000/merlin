<?php

namespace App\Modules\Stations\Contracts;

use App\Modules\Stations\Domain\StationDetails;
use App\Modules\Stations\Domain\StationSearchResponse;

/**
 * Kapselt externe Suchanbieter hinter einem preisfreien, normalisierten Fachvertrag.
 */
interface StationSearchProvider
{
    /** Sucht anhand einer deutschen PLZ und eines freigegebenen Radius. */
    public function search(string $postalCode, int $radius): StationSearchResponse;

    /** Verifiziert eine signierte Trefferreferenz und lädt die zulässigen Stammdaten. */
    public function details(string $signedReference): StationDetails;
}
