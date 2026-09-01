<x-filament-panels::page>
    <div class="merlin-station-create">
        <a class="merlin-back-link" href="{{ $backUrl }}" wire:navigate>← {{ __('stations.actions.back') }}</a>

        <section class="merlin-search-panel is-collapsed" aria-labelledby="station-edit-heading">
            <div class="merlin-selected-station">
                <div class="merlin-selected-station__icon" aria-hidden="true">
                    {{ mb_substr($station->brand?->name ?? $station->name, 0, 1) }}
                </div>
                <div>
                    <span class="merlin-eyebrow">{{ __('stations.edit.eyebrow') }}</span>
                    <h2 id="station-edit-heading">{{ $station->name }}</h2>
                    <p>{{ $station->street }} {{ $station->house_number }}, {{ $station->postal_code }} {{ $station->city }}</p>
                </div>
                <span class="merlin-status-badge is-{{ $station->status }}">
                    {{ __('stations.statuses.'.$station->status) }}
                </span>
            </div>
        </section>

        <form class="merlin-form-panel merlin-edit-form" wire:submit="save">
            <div class="merlin-form-panel__heading">
                <span class="merlin-eyebrow">{{ __('stations.edit.form_eyebrow') }}</span>
                <h2>{{ __('stations.edit.heading') }}</h2>
                <p>{{ __('stations.edit.description') }}</p>
            </div>

            @error('stationVersion')
                <div class="merlin-notice is-error" role="alert">{{ $message }}</div>
            @enderror

            @php
                $generalErrorCount = collect(['legalEntityPublicId', 'brandId', 'name', 'shortName'])
                    ->sum(fn (string $field): int => count($errors->get($field)));
                $addressErrorCount = collect(['street', 'houseNumber', 'addressAddition', 'postalCode', 'city', 'region'])
                    ->sum(fn (string $field): int => count($errors->get($field)));
            @endphp

            <div class="merlin-edit-tabs" role="tablist" aria-label="{{ __('stations.edit.tabs_label') }}">
                <button
                    id="station-tab-general"
                    type="button"
                    role="tab"
                    aria-controls="station-panel-general"
                    aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}"
                    @class(['is-active' => $activeTab === 'general', 'has-errors' => $generalErrorCount > 0])
                    wire:click="selectTab('general')"
                >
                    <span class="merlin-edit-tabs__icon" aria-hidden="true">A</span>
                    <span>
                        <strong>{{ __('stations.tabs.general') }}</strong>
                        <small>{{ __('stations.edit.general_tab_description') }}</small>
                    </span>
                    @if ($generalErrorCount > 0)
                        <span class="merlin-tab-error" aria-label="{{ trans_choice('stations.edit.tab_errors', $generalErrorCount, ['count' => $generalErrorCount]) }}">
                            {{ $generalErrorCount }}
                        </span>
                    @endif
                </button>
                <button
                    id="station-tab-address"
                    type="button"
                    role="tab"
                    aria-controls="station-panel-address"
                    aria-selected="{{ $activeTab === 'address' ? 'true' : 'false' }}"
                    @class(['is-active' => $activeTab === 'address', 'has-errors' => $addressErrorCount > 0])
                    wire:click="selectTab('address')"
                >
                    <span class="merlin-edit-tabs__icon" aria-hidden="true">O</span>
                    <span>
                        <strong>{{ __('stations.tabs.address') }}</strong>
                        <small>{{ __('stations.edit.address_tab_description') }}</small>
                    </span>
                    @if ($addressErrorCount > 0)
                        <span class="merlin-tab-error" aria-label="{{ trans_choice('stations.edit.tab_errors', $addressErrorCount, ['count' => $addressErrorCount]) }}">
                            {{ $addressErrorCount }}
                        </span>
                    @endif
                </button>
            </div>

            @if ($activeTab === 'general')
                <section id="station-panel-general" role="tabpanel" aria-labelledby="station-tab-general" class="merlin-edit-tab-panel">
                    <div class="merlin-edit-tab-panel__heading">
                        <h3>{{ __('stations.create.general_heading') }}</h3>
                        <p>{{ __('stations.edit.general_description') }}</p>
                    </div>
                    <div class="merlin-form-grid">
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.legal_entity') }} *</span>
                            <select wire:model="legalEntityPublicId" @error('legalEntityPublicId') aria-invalid="true" @enderror>
                                @foreach ($legalEntities as $entity)
                                    <option value="{{ $entity->public_id }}">{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('legalEntityPublicId') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.brand') }}</span>
                            <select wire:model="brandId" @error('brandId') aria-invalid="true" @enderror>
                                <option value="">{{ __('stations.values.please_select') }}</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->getKey() }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @error('brandId') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field is-wide">
                            <span>{{ __('stations.fields.name') }} *</span>
                            <input type="text" maxlength="160" wire:model="name" @error('name') aria-invalid="true" @enderror>
                            @error('name') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field is-wide">
                            <span>{{ __('stations.fields.short_name') }}</span>
                            <input type="text" maxlength="80" wire:model="shortName" @error('shortName') aria-invalid="true" @enderror>
                            @error('shortName') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>
            @else
                <section id="station-panel-address" role="tabpanel" aria-labelledby="station-tab-address" class="merlin-edit-tab-panel">
                    <div class="merlin-edit-tab-panel__heading">
                        <h3>{{ __('stations.create.address_heading') }}</h3>
                        <p>{{ __('stations.edit.address_description') }}</p>
                    </div>
                    <div class="merlin-form-grid">
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.street') }} *</span>
                            <input type="text" maxlength="160" wire:model="street" @error('street') aria-invalid="true" @enderror>
                            @error('street') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.house_number') }} *</span>
                            <input type="text" maxlength="30" wire:model="houseNumber" @error('houseNumber') aria-invalid="true" @enderror>
                            @error('houseNumber') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field is-wide">
                            <span>{{ __('stations.fields.address_addition') }}</span>
                            <input type="text" maxlength="120" wire:model="addressAddition" @error('addressAddition') aria-invalid="true" @enderror>
                            @error('addressAddition') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.postal_code') }} *</span>
                            <input type="text" inputmode="numeric" maxlength="5" wire:model="postalCode" @error('postalCode') aria-invalid="true" @enderror>
                            @error('postalCode') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field">
                            <span>{{ __('stations.fields.city') }} *</span>
                            <input type="text" maxlength="120" wire:model="city" @error('city') aria-invalid="true" @enderror>
                            @error('city') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label class="merlin-field is-wide">
                            <span>{{ __('stations.fields.region') }} *</span>
                            <input type="text" maxlength="120" wire:model="region" @error('region') aria-invalid="true" @enderror>
                            @error('region') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>
            @endif

            @if ($duplicateWarning)
                <div class="merlin-notice is-warning">
                    <strong>{{ __('stations.duplicate.heading') }}</strong>
                    <p>{{ __('stations.duplicate.edit_description') }}</p>
                    <label class="merlin-field">
                        <span>{{ __('stations.fields.duplicate_reason') }} *</span>
                        <textarea rows="3" maxlength="500" wire:model="duplicateReason"></textarea>
                        @error('duplicateReason') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                </div>
            @endif

            <aside class="merlin-edit-context" aria-label="{{ __('stations.edit.unchanged_data') }}">
                <div>
                    <span>{{ __('stations.fields.status') }}</span>
                    <strong>{{ __('stations.statuses.'.$station->status) }}</strong>
                </div>
                <div>
                    <span>{{ __('stations.fields.source') }}</span>
                    <strong>{{ __('stations.sources.'.$station->source_type) }}</strong>
                </div>
                <small>{{ __('stations.edit.status_and_source_unchanged') }}</small>
            </aside>

            <div class="merlin-edit-actions">
                <a class="merlin-secondary-button" href="{{ $backUrl }}" wire:navigate>{{ __('stations.actions.cancel') }}</a>
                <span class="merlin-edit-actions__dirty" wire:dirty wire:target="legalEntityPublicId,brandId,name,shortName,street,houseNumber,addressAddition,postalCode,city,region">
                    {{ __('stations.edit.unsaved_changes') }}
                </span>
                <button class="merlin-primary-button" type="submit" wire:loading.attr="disabled">
                    {{ __('stations.actions.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
