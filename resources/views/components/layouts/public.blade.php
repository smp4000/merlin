@props(['title' => config('app.name'), 'noindex' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="merlin-public-shell">
        <header class="merlin-public-header">
            <a href="{{ url('/') }}" class="merlin-wordmark" aria-label="Merlin Startseite">
                <span class="merlin-wordmark-mark" aria-hidden="true">M</span>
                <span><strong>Merlin</strong><small>Betriebsplattform</small></span>
            </a>
            <nav class="merlin-header-actions" aria-label="Schnellzugriff">
                <a href="{{ request()->fullUrlWithQuery(['lang' => app()->getLocale() === 'de' ? 'en' : 'de']) }}" class="merlin-language-link">{{ app()->getLocale() === 'de' ? 'EN' : 'DE' }}</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="merlin-header-link">{{ __('registration.actions.login') }}</a>
            </nav>
        </header>

        <main>{{ $slot }}</main>

        <footer class="merlin-public-footer">
            <span>© {{ now()->year }} Merlin</span>
            <nav aria-label="Rechtliche Hinweise">
                <a href="{{ route('legal.terms') }}">{{ __('registration.legal.terms') }}</a>
                <a href="{{ route('legal.privacy') }}">{{ __('registration.legal.privacy') }}</a>
                <button type="button" data-privacy-open>{{ __('privacy.settings') }}</button>
            </nav>
        </footer>

        <x-privacy-consent />
    </body>
</html>
