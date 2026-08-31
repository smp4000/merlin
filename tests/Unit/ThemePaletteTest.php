<?php

namespace Tests\Unit;

use App\Enums\ThemePalette;
use Filament\Support\Colors\Color;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prüft den kontrollierten Farbkatalog der Merlin-Oberfläche.
 */
final class ThemePaletteTest extends TestCase
{
    /**
     * Alle freigegebenen Paletten müssen vollständig sein und für weißen Buttontext
     * mindestens den WCAG-AA-Kontrast bereitstellen.
     */
    #[DataProvider('paletteProvider')]
    public function test_palette_is_complete_and_accessible(ThemePalette $palette): void
    {
        $colors = $palette->colors();

        $this->assertSame([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950], array_keys($colors));
        $this->assertGreaterThanOrEqual(
            Color::WCAG_AA_TEXT,
            Color::calculateContrastRatio('#ffffff', $colors[700]),
        );
    }

    /**
     * @return iterable<string, array{ThemePalette}>
     */
    public static function paletteProvider(): iterable
    {
        foreach (ThemePalette::cases() as $palette) {
            yield $palette->value => [$palette];
        }
    }

    /**
     * Der neutrale Einstieg verwendet immer das bestätigte Merlin-Petrol.
     */
    public function test_default_palette_is_merlin_petrol(): void
    {
        $this->assertSame(ThemePalette::MerlinPetrol, ThemePalette::default());
    }

    /** Jede Palette stellt alle benötigten, ausschließlich katalogbasierten CSS-Werte bereit. */
    #[DataProvider('paletteProvider')]
    public function test_palette_exposes_complete_css_variables(ThemePalette $palette): void
    {
        $this->assertSame(
            ['accent', 'accent_hover', 'accent_soft', 'accent_soft_strong', 'accent_focus'],
            array_keys($palette->cssVariables()),
        );

        foreach ($palette->cssVariables() as $value) {
            $this->assertStringStartsWith('oklch(', $value);
        }
    }
}
