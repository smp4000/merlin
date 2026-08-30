<?php

namespace Tests\Unit;

use App\Modules\Banking\Application\GermanIban;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Prüft die reine deutsche Standardberechnung unabhängig von Datenbank und Oberfläche.
 */
final class GermanIbanTest extends TestCase
{
    #[Test]
    public function it_calculates_and_validates_a_published_standard_example(): void
    {
        $service = new GermanIban;

        self::assertSame('DE89370400440532013000', $service->calculate('37040044', '532013000'));
        self::assertSame('DE84530601800300250503', $service->calculate('53060180', '300250503'));
        self::assertSame('DE89370400440532013000', $service->normalizeAndValidate('DE89 3704 0044 0532 0130 00'));
        self::assertSame('37040044', $service->bankCode('DE89370400440532013000'));
    }

    #[Test]
    public function it_rejects_an_invalid_checksum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new GermanIban)->normalizeAndValidate('DE00370400440532013000');
    }
}
