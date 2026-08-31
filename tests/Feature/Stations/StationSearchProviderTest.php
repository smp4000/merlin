<?php

namespace Tests\Feature\Stations;

use App\Modules\Stations\Application\Exceptions\StationSearchUnavailableException;
use App\Modules\Stations\Contracts\StationSearchProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Prüft den preisfreien Providervertrag, Radiusregeln und manipulierte Referenzen.
 */
final class StationSearchProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('merlin.station_search.enabled', true);
        Cache::clear();
    }

    public function test_provider_returns_normalized_results_without_prices(): void
    {
        Http::fake([
            '*/sqlite/slite.php' => Http::response('<span class="uselect" data-zipm="36100-petersberg">36100 Petersberg</span>'),
            '*36100-petersberg-aktuelle-e10preise*' => Http::response($this->searchHtml()),
        ]);

        $response = app(StationSearchProvider::class)->search('36100', 10);

        self::assertCount(1, $response->results);
        self::assertSame('ARAL Tankstelle Petersberg', $response->results[0]->name);
        self::assertSame('Petersberger Straße 101', $response->results[0]->street);
        self::assertArrayNotHasKey('price', $response->results[0]->toArray());
        Http::assertSentCount(2);
    }

    public function test_requested_radius_is_validated_and_twenty_five_kilometres_warns_about_source_limit(): void
    {
        Http::fake([
            '*/sqlite/slite.php' => Http::response('<span data-zipm="36100-petersberg">36100 Petersberg</span>'),
            '*umkreis=20' => Http::response($this->searchHtml()),
        ]);

        $response = app(StationSearchProvider::class)->search('36100', 25);
        self::assertNotNull($response->warning);

        $this->expectException(ValidationException::class);
        app(StationSearchProvider::class)->search('36100', 7);
    }

    public function test_signed_reference_is_verified_before_details_are_loaded(): void
    {
        Http::fake([
            '*/sqlite/slite.php' => Http::response('<span data-zipm="36100-petersberg">36100 Petersberg</span>'),
            '*aktuelle-e10preise*' => Http::response($this->searchHtml()),
            '*/preise-tabc12-petersberg-aral' => Http::response($this->detailsHtml()),
        ]);

        $result = app(StationSearchProvider::class)->search('36100', 5)->results[0];
        $details = app(StationSearchProvider::class)->details($result->reference);

        self::assertSame('36100', $details->postalCode);
        self::assertSame('Petersberger Straße', $details->street);
        self::assertSame('101', $details->houseNumber);
        self::assertSame('provider-station-1', $details->externalStationId);

        $this->expectException(ValidationException::class);
        app(StationSearchProvider::class)->details($result->reference.'tampered');
    }

    public function test_disabled_provider_fails_closed_without_http_request(): void
    {
        Config::set('merlin.station_search.enabled', false);
        Http::fake();

        $this->expectException(StationSearchUnavailableException::class);
        try {
            app(StationSearchProvider::class)->search('36100', 5);
        } finally {
            Http::assertNothingSent();
        }
    }

    private function searchHtml(): string
    {
        return <<<'HTML'
<div id="station-tabc12-petersberg-aral" data-mid="provider-station-1">
  <div class="ns_price"><em title="E10-Preis: 1.999 Euro">1.99</em><sup>9</sup></div>
  <p><strong class="isstrong">ARAL Tankstelle Petersberg</strong><br>Petersberger Straße 101<span>24h</span></p>
</div><div class="sxi"></div>
HTML;
    }

    private function detailsHtml(): string
    {
        return <<<'HTML'
<html><head>
<title>ARAL Tankstelle Petersberg | Spritpreise &amp; Öffnungszeiten</title>
<meta property="place:location:latitude" content="50.5600000">
<meta property="place:location:longitude" content="9.7100000">
</head><body>
<h2>Wo finde ich die Tankstelle?</h2><p>Petersberger Straße 101<br>36100 Petersberg</p>
<div class="price">1.999 Euro</div>
</body></html>
HTML;
    }
}
