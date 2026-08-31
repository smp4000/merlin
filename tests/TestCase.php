<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Startet Tests niemals mit einer möglicherweise auf MySQL zeigenden Konfigurationsdatei.
     *
     * Eine lokale `config:cache`-Datei enthält bereits aufgelöste `.env`-Werte und würde
     * deshalb selbst die erzwungenen PHPUnit-Variablen übergehen. Das Entfernen dieses
     * ausschließlich generierten Caches geschieht vor dem Laravel-Bootstrap; anschließend
     * greift sicher `sqlite :memory:` aus der PHPUnit-Konfiguration.
     */
    public function createApplication(): Application
    {
        $cachedConfig = dirname(__DIR__).'/bootstrap/cache/config.php';

        if (is_file($cachedConfig) && ! unlink($cachedConfig)) {
            throw new RuntimeException('Der Konfigurationscache konnte vor dem sicheren Teststart nicht entfernt werden.');
        }

        return parent::createApplication();
    }
}
