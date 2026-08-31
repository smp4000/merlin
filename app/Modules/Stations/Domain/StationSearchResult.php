<?php

namespace App\Modules\Stations\Domain;

/**
 * Enthält einen normalisierten, preisfreien Treffer einer externen Standortsuche.
 */
final readonly class StationSearchResult
{
    public function __construct(
        public string $reference,
        public string $name,
        public string $street,
        public string $city,
        public ?bool $isOpen,
    ) {}

    /** @return array{reference: string, name: string, street: string, city: string, is_open: bool|null} */
    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'name' => $this->name,
            'street' => $this->street,
            'city' => $this->city,
            'is_open' => $this->isOpen,
        ];
    }
}
