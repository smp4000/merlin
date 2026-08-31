@props(['title' => 'Merlin Onboarding', 'tagline' => 'Geschütztes Onboarding'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="merlin-public-shell merlin-onboarding-shell">
        <header class="merlin-public-header">
            <a href="{{ url('/') }}" class="merlin-wordmark" aria-label="Merlin Startseite">
                <span class="merlin-wordmark-mark" aria-hidden="true">M</span>
                <span><strong>Merlin</strong><small>{{ $tagline }}</small></span>
            </a>
            <span class="merlin-onboarding-user">{{ auth()->user()->name }}</span>
        </header>
        <main>{{ $slot }}</main>
    </body>
</html>
