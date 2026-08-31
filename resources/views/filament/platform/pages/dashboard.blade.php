<x-filament-panels::page>
    <section class="merlin-welcome" aria-labelledby="platform-welcome-heading">
        <div class="merlin-welcome__content">
            <span class="merlin-eyebrow">{{ __('merlin.platform_dashboard.eyebrow') }}</span>
            <h2 id="platform-welcome-heading">
                {{ __('merlin.platform_dashboard.welcome', ['name' => auth()->user()?->name]) }}
            </h2>
            <p>{{ __('merlin.platform_dashboard.introduction') }}</p>
        </div>

        <div class="merlin-welcome__status" role="status">
            <span class="merlin-status-dot" aria-hidden="true"></span>
            <span>{{ __('merlin.platform_dashboard.status') }}</span>
        </div>
    </section>

    <section class="merlin-progress" aria-labelledby="platform-boundary-heading">
        <div class="merlin-section-heading">
            <div>
                <span class="merlin-eyebrow">{{ __('merlin.platform_dashboard.boundary.eyebrow') }}</span>
                <h2 id="platform-boundary-heading">{{ __('merlin.platform_dashboard.boundary.heading') }}</h2>
            </div>
        </div>
        <p class="merlin-platform-boundary-copy">{{ __('merlin.platform_dashboard.boundary.description') }}</p>
    </section>

    <section class="merlin-dashboard-grid" aria-label="{{ __('merlin.platform_dashboard.cards.aria_label') }}">
        @foreach (__('merlin.platform_dashboard.cards.items') as $card)
            <article class="merlin-dashboard-card">
                <span class="merlin-dashboard-card__icon" aria-hidden="true">{{ $card['symbol'] }}</span>
                <div>
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['description'] }}</p>
                </div>
                <span class="merlin-dashboard-card__state">{{ $card['state'] }}</span>
            </article>
        @endforeach
    </section>
</x-filament-panels::page>
