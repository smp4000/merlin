<x-filament-panels::page>
    <section class="merlin-station-heading">
        <div>
            <span class="merlin-eyebrow">{{ __('stations.selection.eyebrow') }}</span>
            <h2>{{ __('stations.selection.heading') }}</h2>
            <p>{{ __('stations.selection.introduction') }}</p>
        </div>
    </section>

    <section class="merlin-station-list" aria-label="{{ __('stations.selection.aria_label') }}">
        @forelse ($stations as $station)
            <article class="merlin-station-card">
                <div class="merlin-station-card__brand" aria-hidden="true">
                    {{ mb_substr($station->brand?->name ?? $station->name, 0, 1) }}
                </div>
                <div class="merlin-station-card__content">
                    <div class="merlin-station-card__title">
                        <div>
                            <h3>{{ $station->name }}</h3>
                            <p>{{ $station->street }} {{ $station->house_number }}, {{ $station->postal_code }} {{ $station->city }}</p>
                        </div>
                        @if ($activeStation?->is($station))
                            <span class="merlin-status-badge is-active">{{ __('stations.selection.current') }}</span>
                        @else
                            <button
                                class="merlin-primary-button"
                                type="button"
                                wire:click="selectStation('{{ $station->public_id }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('stations.actions.select_for_work') }}
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="merlin-empty-state">
                <span aria-hidden="true">T</span>
                <h3>{{ __('stations.selection.empty_heading') }}</h3>
                <p>{{ __('stations.selection.empty_description') }}</p>
                <a class="merlin-primary-button" href="{{ $stationOverviewUrl }}" wire:navigate>
                    {{ __('stations.selection.open_management') }}
                </a>
            </div>
        @endforelse
    </section>
</x-filament-panels::page>
