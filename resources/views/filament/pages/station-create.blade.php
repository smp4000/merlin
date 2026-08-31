<x-filament-panels::page>
    <div class="merlin-station-create">
        <a class="merlin-back-link" href="{{ $backUrl }}" wire:navigate>← {{ __('stations.actions.back') }}</a>

        <section class="merlin-search-panel" aria-labelledby="station-search-heading">
            <div class="merlin-search-panel__intro">
                <span class="merlin-eyebrow">{{ $linkStation ? __('stations.link.eyebrow') : __('stations.create.eyebrow') }}</span>
                <h2 id="station-search-heading">
                    {{ $linkStation ? __('stations.link.heading', ['station' => $linkStation->name]) : __('stations.search.heading') }}
                </h2>
                <p>{{ __('stations.search.source_note') }}</p>
            </div>

            @if ($searchEnabled)
                <form class="merlin-search-form" wire:submit="search">
                    <label>
                        <span>{{ __('stations.fields.postal_code') }}</span>
                        <input type="text" inputmode="numeric" maxlength="5" wire:model="postalCode" autocomplete="postal-code">
                        @error('postalCode') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.radius') }}</span>
                        <select wire:model="radius">
                            @foreach ($radii as $availableRadius)
                                <option value="{{ $availableRadius }}">{{ $availableRadius }} km</option>
                            @endforeach
                        </select>
                        @error('radius') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <button class="merlin-primary-button" type="submit" wire:loading.attr="disabled" wire:target="search">
                        <span wire:loading.remove wire:target="search">{{ __('stations.actions.search') }}</span>
                        <span wire:loading wire:target="search">{{ __('stations.actions.searching') }}</span>
                    </button>
                </form>
            @else
                <div class="merlin-notice is-warning" role="status">{{ __('stations.search.disabled') }}</div>
            @endif

            @if ($searchWarning)
                <div class="merlin-notice is-warning" role="status">{{ $searchWarning }}</div>
            @endif

            @error('selectedReference')
                <div class="merlin-notice is-error" role="alert">{{ $message }}</div>
            @enderror

            @if ($searchResults !== [])
                <div class="merlin-search-results" aria-live="polite">
                    <h3>{{ trans_choice('stations.search.result_count', count($searchResults), ['count' => count($searchResults)]) }}</h3>
                    <div class="merlin-search-results__list">
                        @foreach ($searchResults as $index => $result)
                            <article class="merlin-search-result" wire:key="station-result-{{ $index }}">
                                <div>
                                    <h4>{{ $result['name'] }}</h4>
                                    <p>{{ $result['street'] }}{{ $result['city'] ? ', '.$result['city'] : '' }}</p>
                                    @if ($result['is_open'] !== null)
                                        <span class="merlin-open-state {{ $result['is_open'] ? 'is-open' : 'is-closed' }}">
                                            {{ $result['is_open'] ? __('stations.values.open') : __('stations.values.closed') }}
                                        </span>
                                    @endif
                                </div>
                                <button class="merlin-secondary-button" type="button" wire:click="selectResult('{{ $result['reference'] }}')" wire:loading.attr="disabled">
                                    {{ __('stations.actions.select') }}
                                </button>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! $linkStation)
                <button class="merlin-text-button" type="button" wire:click="startManual">
                    {{ __('stations.actions.manual') }}
                </button>
            @endif
        </section>

        @if ($linkStation && $linkComparison)
            <section class="merlin-form-panel" aria-labelledby="link-review-heading">
                <span class="merlin-eyebrow">{{ __('stations.link.review_eyebrow') }}</span>
                <h2 id="link-review-heading">{{ __('stations.link.review_heading') }}</h2>
                <p>{{ __('stations.link.no_overwrite') }}</p>
                <div class="merlin-comparison-grid">
                    <article>
                        <strong>{{ __('stations.link.current_data') }}</strong>
                        <span>{{ $linkStation->name }}</span>
                        <small>{{ $linkComparison['current'] }}</small>
                    </article>
                    <article>
                        <strong>{{ __('stations.link.directory_data') }}</strong>
                        <span>{{ $linkComparison['external_name'] }}</span>
                        <small>{{ $linkComparison['external'] }}</small>
                    </article>
                </div>
                <button class="merlin-primary-button" type="button" wire:click="confirmLink" wire:loading.attr="disabled">
                    {{ __('stations.actions.confirm_link') }}
                </button>
            </section>
        @endif

        @if (! $linkStation && $detailsVisible)
            <form class="merlin-form-panel" wire:submit="save">
                <div class="merlin-form-panel__heading">
                    <span class="merlin-eyebrow">{{ __('stations.create.details_eyebrow') }}</span>
                    <h2>{{ __('stations.create.details_heading') }}</h2>
                    <p>{{ $manualMode ? __('stations.create.manual_description') : __('stations.create.search_description') }}</p>
                </div>

                <div class="merlin-form-tabs" role="tablist" aria-label="{{ __('stations.create.tabs_label') }}">
                    <span class="is-active">1 · {{ __('stations.tabs.general') }}</span>
                    <span>2 · {{ __('stations.tabs.address') }}</span>
                    <span>3 · {{ __('stations.tabs.review') }}</span>
                </div>

                <div class="merlin-form-grid">
                    <label>
                        <span>{{ __('stations.fields.legal_entity') }} *</span>
                        <select wire:model="legalEntityPublicId">
                            @foreach ($legalEntities as $entity)
                                <option value="{{ $entity->public_id }}">{{ $entity->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('legalEntityPublicId') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.brand') }}</span>
                        <select wire:model="brandId">
                            <option value="">{{ __('stations.values.please_select') }}</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->getKey() }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brandId') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="is-wide">
                        <span>{{ __('stations.fields.name') }} *</span>
                        <input type="text" maxlength="160" wire:model="name">
                        @error('name') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.short_name') }}</span>
                        <input type="text" maxlength="80" wire:model="shortName">
                        @error('shortName') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.street') }} *</span>
                        <input type="text" maxlength="160" wire:model="street">
                        @error('street') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.house_number') }} *</span>
                        <input type="text" maxlength="30" wire:model="houseNumber">
                        @error('houseNumber') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.postal_code') }} *</span>
                        <input type="text" inputmode="numeric" maxlength="5" wire:model="postalCode">
                        @error('postalCode') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.city') }} *</span>
                        <input type="text" maxlength="120" wire:model="city">
                        @error('city') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>{{ __('stations.fields.region') }} *</span>
                        <input type="text" maxlength="120" wire:model="region">
                        @error('region') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                </div>

                @if ($duplicateWarning)
                    <div class="merlin-notice is-warning" role="alert">
                        <strong>{{ __('stations.duplicate.heading') }}</strong>
                        <p>{{ __('stations.duplicate.description') }}</p>
                        <label>
                            <span>{{ __('stations.fields.duplicate_reason') }} *</span>
                            <textarea rows="3" maxlength="500" wire:model="duplicateReason"></textarea>
                            @error('duplicateReason') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                @endif

                <div class="merlin-form-actions">
                    <a class="merlin-secondary-button" href="{{ $backUrl }}" wire:navigate>{{ __('stations.actions.cancel') }}</a>
                    <button class="merlin-primary-button" type="submit" wire:loading.attr="disabled">
                        {{ __('stations.actions.save_draft') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-filament-panels::page>
