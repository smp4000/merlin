<x-filament-panels::page>
    <section class="merlin-welcome" aria-labelledby="merlin-welcome-heading">
        <div class="merlin-welcome__content">
            <span class="merlin-eyebrow">{{ __('merlin.dashboard.eyebrow') }}</span>
            <h2 id="merlin-welcome-heading">
                {{ __('merlin.dashboard.welcome', ['name' => auth()->user()?->name ?? __('merlin.dashboard.fallback_name')]) }}
            </h2>
            <p>
                {{ $tenantProgress
                    ? __('merlin.dashboard.partner_introduction', ['tenant' => $tenantProgress['tenant_name']])
                    : __('merlin.dashboard.introduction') }}
            </p>
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
            <span class="merlin-progress__counter">
                {{ __('merlin.dashboard.progress.counter', [
                    'current' => $tenantProgress['current_step'] ?? 1,
                    'total' => $tenantProgress['total_steps'] ?? 4,
                ]) }}
            </span>
        </div>

        <ol class="merlin-steps">
            @foreach (__('merlin.dashboard.progress.steps') as $index => $step)
                @php
                    $isComplete = $tenantProgress['completed_steps'][$index] ?? false;
                    $isCurrent = $tenantProgress
                        ? $index === $tenantProgress['current_step'] - 1
                        : $index === 0;
                @endphp
                <li class="merlin-step {{ $isComplete ? 'is-complete' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
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
        @foreach (__('merlin.dashboard.cards.items') as $index => $card)
            <article class="merlin-dashboard-card">
                <span class="merlin-dashboard-card__icon" aria-hidden="true">{{ $card['symbol'] }}</span>
                <div>
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['description'] }}</p>
                </div>
                <span class="merlin-dashboard-card__state">
                    @if ($tenantProgress && $index < 2 && ($tenantProgress['completed_steps'][$index] ?? false))
                        {{ __('merlin.dashboard.cards.states.ready') }}
                    @elseif ($tenantProgress && $index === $tenantProgress['current_step'] - 1)
                        {{ __('merlin.dashboard.cards.states.next') }}
                    @else
                        {{ $card['state'] }}
                    @endif
                </span>
            </article>
        @endforeach
    </section>
</x-filament-panels::page>
