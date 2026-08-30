<?php

use App\Http\Middleware\ApplyLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Geschützte Partnerseiten verwenden den Filament-Login. Ohne diese explizite
        // Zielroute würde Laravels Auth-Middleware bei Gästen auf die nicht vorhandene
        // Standardroute `login` verweisen und fälschlich einen HTTP-500-Fehler auslösen.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        $middleware->web(append: [ApplyLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ein Bestätigungstoken ist ein kurzlebiges Secret und darf nie als alter
        // Formulareingabewert in der serverseitigen Sitzung gespeichert werden.
        $exceptions->dontFlash(['confirmation_token']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
