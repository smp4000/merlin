<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

/**
 * Definiert die von Merlin geprüften Akzentpaletten für mandantenbezogene Oberflächen.
 *
 * Partner wählen später ausschließlich einen stabilen Schlüssel aus dieser Liste. Dadurch
 * gelangen weder beliebiges CSS noch ungeprüfte Farben aus Mandanteneingaben in die Oberfläche.
 */
enum ThemePalette: string
{
    case MerlinPetrol = 'merlin-petrol';
    case OceanBlue = 'ocean-blue';
    case ForestGreen = 'forest-green';
    case Violet = 'violet';
    case Coral = 'coral';
    case Graphite = 'graphite';

    /**
     * Liefert die systemweite Standardpalette für neutrale und noch nicht zugeordnete Kontexte.
     */
    public static function default(): self
    {
        return self::MerlinPetrol;
    }

    /**
     * Gibt den Übersetzungsschlüssel für den sichtbaren Palettennamen zurück.
     */
    public function labelKey(): string
    {
        return "merlin.theme.palettes.{$this->value}";
    }

    /**
     * Liefert eine vollständige Filament-Farbskala für Buttons, Fokus und Navigation.
     *
     * Statusfarben wie Erfolg, Warnung und Fehler werden bewusst nicht verändert, weil ihre
     * Semantik mandantenübergreifend eindeutig und barrierefrei bleiben muss.
     *
     * @return array<int, string>
     */
    public function colors(): array
    {
        return match ($this) {
            self::MerlinPetrol => Color::Teal,
            self::OceanBlue => Color::Blue,
            self::ForestGreen => Color::Emerald,
            self::Violet => Color::Violet,
            self::Coral => Color::Rose,
            self::Graphite => Color::Slate,
        };
    }

    /**
     * Liefert ausschließlich geprüfte CSS-Werte für das mandantenbezogene Merlin-Theme.
     *
     * Die Werte stammen aus dem festen Farbkatalog und niemals aus freiem Benutzereingang.
     * Dadurch kann die Oberfläche sie ohne CSS-Injection-Risiko als Variablen ausgeben.
     *
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        $colors = $this->colors();

        return [
            'accent' => $colors[700],
            'accent_hover' => $colors[800],
            'accent_soft' => $colors[100],
            'accent_soft_strong' => $colors[200],
            'accent_focus' => $colors[300],
        ];
    }
}
