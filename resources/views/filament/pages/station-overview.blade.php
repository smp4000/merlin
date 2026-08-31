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
        @if ($stations->isEmpty())
            <div class="merlin-empty-state">
                <span aria-hidden="true">T</span>
                <h3>{{ __('stations.overview.empty_heading') }}</h3>
                <p>{{ __('stations.overview.empty_description') }}</p>
                <a class="merlin-primary-button" href="{{ $createUrl }}" wire:navigate>{{ __('stations.actions.create') }}</a>
            </div>
        @elseif ($stations->count() <= 2)
            @foreach ($stations as $station)
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
                        <div class="merlin-station-actions">
                            <a class="merlin-primary-button" href="{{ \App\Filament\Pages\StationEdit::getUrl(['station' => $station->public_id]) }}" wire:navigate>
                                {{ __('stations.actions.edit') }}
                            </a>
                            @if ($station->sourceReferences->isEmpty())
                                <a class="merlin-secondary-button" href="{{ \App\Filament\Pages\StationCreate::getUrl(['station' => $station->public_id]) }}" wire:navigate>
                                    {{ __('stations.actions.link_directory') }}
                                </a>
                            @else
                                <span class="merlin-linked-note">{{ __('stations.values.directory_linked') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        @else
            <div class="merlin-station-table-wrap" role="region" tabindex="0" aria-label="{{ __('stations.overview.table_aria_label') }}">
                <table class="merlin-station-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('stations.fields.name') }}</th>
                            <th scope="col">{{ __('stations.tabs.address') }}</th>
                            <th scope="col">{{ __('stations.fields.brand') }}</th>
                            <th scope="col">{{ __('stations.fields.legal_entity') }}</th>
                            <th scope="col">{{ __('stations.fields.source') }}</th>
                            <th scope="col">{{ __('stations.fields.status') }}</th>
                            <th scope="col"><span class="sr-only">{{ __('stations.fields.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stations as $station)
                            <tr>
                                <th scope="row">
                                    <span class="merlin-table-station">
                                        <span aria-hidden="true">{{ mb_substr($station->brand?->name ?? $station->name, 0, 1) }}</span>
                                        <strong>{{ $station->name }}</strong>
                                    </span>
                                </th>
                                <td>{{ $station->street }} {{ $station->house_number }}<br><small>{{ $station->postal_code }} {{ $station->city }}</small></td>
                                <td>{{ $station->brand?->name ?? __('stations.values.not_assigned') }}</td>
                                <td>{{ $station->legalEntity?->legal_name ?? __('stations.values.not_assigned') }}</td>
                                <td>{{ __('stations.sources.'.$station->source_type) }}</td>
                                <td>
                                    <span class="merlin-status-badge is-{{ $station->status }}">
                                        {{ __('stations.statuses.'.$station->status) }}
                                    </span>
                                </td>
                                <td class="merlin-station-table__action">
                                    <div class="merlin-station-actions">
                                        <a class="merlin-primary-button" href="{{ \App\Filament\Pages\StationEdit::getUrl(['station' => $station->public_id]) }}" wire:navigate>
                                            {{ __('stations.actions.edit') }}
                                        </a>
                                        @if ($station->sourceReferences->isEmpty())
                                            <a class="merlin-secondary-button" href="{{ \App\Filament\Pages\StationCreate::getUrl(['station' => $station->public_id]) }}" wire:navigate>
                                                {{ __('stations.actions.link_short') }}
                                            </a>
                                        @else
                                            <span class="merlin-linked-note">{{ __('stations.values.directory_linked_short') }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-filament-panels::page>
