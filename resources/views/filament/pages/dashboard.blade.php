<x-filament-panels::page>
    <section class="merlin-welcome" aria-labelledby="merlin-welcome-heading">
        <div class="merlin-welcome__content">
            <span class="merlin-eyebrow">{{ __('merlin.dashboard.eyebrow') }}</span>
            <h2 id="merlin-welcome-heading">
                {{ __('merlin.dashboard.welcome', ['name' => auth()->user()?->name ?? __('merlin.dashboard.fallback_name')]) }}
            </h2>
            <p>{{ __('merlin.dashboard.introduction') }}</p>
        </div>

        <div class="merlin-welcome__status" role="status">
            <span class="merlin-status-dot" aria-hidden="true"></span>
            <span>{{ __('merlin.dashboard.status') }}</span>
        </div>
    </section>

    <section class="merlin-progress" aria-labelledby="merlin-progress-heading">
        <div class="merlin-section-heading">
            <div>
                <span class="merlin-eyebrow">{{ __('merlin.dashboard.progress.eyebrow') }}</span>
                <h2 id="merlin-progress-heading">{{ __('merlin.dashboard.progress.heading') }}</h2>
            </div>
            <span class="merlin-progress__counter">{{ __('merlin.dashboard.progress.counter') }}</span>
        </div>

        <ol class="merlin-steps">
            @foreach (__('merlin.dashboard.progress.steps') as $index => $step)
                <li class="merlin-step {{ $index === 0 ? 'is-current' : '' }}">
                    <span class="merlin-step__number">{{ $index + 1 }}</span>
                    <span>
                        <strong>{{ $step['title'] }}</strong>
                        <small>{{ $step['description'] }}</small>
                    </span>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="merlin-dashboard-grid" aria-label="{{ __('merlin.dashboard.cards.aria_label') }}">
        @foreach (__('merlin.dashboard.cards.items') as $card)
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
