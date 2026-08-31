<x-filament-panels::page>
    <section class="merlin-station-heading">
        <div>
            <span class="merlin-eyebrow">{{ __('stations.overview.eyebrow') }}</span>
            <h2>{{ __('stations.overview.heading') }}</h2>
            <p>{{ __('stations.overview.introduction') }}</p>
        </div>
        <a class="merlin-primary-button" href="{{ $createUrl }}" wire:navigate>
            {{ __('stations.actions.create') }}
        </a>
    </section>

    <section class="merlin-station-list" aria-label="{{ __('stations.overview.aria_label') }}">
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
                        <span class="merlin-status-badge is-{{ $station->status }}">
                            {{ __('stations.statuses.'.$station->status) }}
                        </span>
                    </div>

                    <dl class="merlin-station-meta">
                        <div>
                            <dt>{{ __('stations.fields.brand') }}</dt>
                            <dd>{{ $station->brand?->name ?? __('stations.values.not_assigned') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('stations.fields.legal_entity') }}</dt>
                            <dd>{{ $station->legalEntity?->legal_name ?? __('stations.values.not_assigned') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('stations.fields.source') }}</dt>
                            <dd>{{ __('stations.sources.'.$station->source_type) }}</dd>
                        </div>
                    </dl>

                    <div class="merlin-station-card__actions">
                        @if ($station->sourceReferences->isEmpty())
                            <a class="merlin-secondary-button" href="{{ \App\Filament\Pages\StationCreate::getUrl(['station' => $station->public_id]) }}" wire:navigate>
                                {{ __('stations.actions.link_directory') }}
                            </a>
                        @else
                            <span class="merlin-linked-note">{{ __('stations.values.directory_linked') }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="merlin-empty-state">
                <span aria-hidden="true">T</span>
                <h3>{{ __('stations.overview.empty_heading') }}</h3>
                <p>{{ __('stations.overview.empty_description') }}</p>
                <a class="merlin-primary-button" href="{{ $createUrl }}" wire:navigate>{{ __('stations.actions.create') }}</a>
            </div>
        @endforelse
    </section>
</x-filament-panels::page>
