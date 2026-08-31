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

        <form class="merlin-form-panel" wire:submit="save">
            <div class="merlin-form-panel__heading">
                <span class="merlin-eyebrow">{{ __('stations.edit.form_eyebrow') }}</span>
                <h2>{{ __('stations.edit.heading') }}</h2>
                <p>{{ __('stations.edit.description') }}</p>
            </div>

            @error('stationVersion')
                <div class="merlin-notice is-error" role="alert">{{ $message }}</div>
            @enderror

            <div class="merlin-form-tabs" aria-label="{{ __('stations.create.tabs_label') }}">
                @foreach ([1 => 'general', 2 => 'address', 3 => 'review'] as $step => $label)
                    <div @class(['is-active' => $wizardStep === $step, 'is-complete' => $wizardStep > $step])
                         @if ($wizardStep === $step) aria-current="step" @endif>
                        <span>{{ $wizardStep > $step ? '✓' : $step }}</span>
                        <strong>{{ __('stations.tabs.'.$label) }}</strong>
                    </div>
                @endforeach
            </div>

            @if ($wizardStep === 1)
                <fieldset class="merlin-wizard-step">
                    <legend>{{ __('stations.create.general_heading') }}</legend>
                    <p>{{ __('stations.edit.general_description') }}</p>
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
                </fieldset>
            @elseif ($wizardStep === 2)
                <fieldset class="merlin-wizard-step">
                    <legend>{{ __('stations.create.address_heading') }}</legend>
                    <p>{{ __('stations.edit.address_description') }}</p>
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
                </fieldset>
            @else
                <section class="merlin-wizard-step" aria-labelledby="station-edit-review-heading">
                    <h3 id="station-edit-review-heading">{{ __('stations.edit.review_heading') }}</h3>
                    <p>{{ __('stations.edit.review_description') }}</p>
                    <div class="merlin-station-review">
                        <article>
                            <span>{{ __('stations.tabs.general') }}</span>
                            <strong>{{ $name }}</strong>
                            <small>{{ $brands->firstWhere('id', $brandId)?->name ?? __('stations.values.not_assigned') }}</small>
                        </article>
                        <article>
                            <span>{{ __('stations.tabs.address') }}</span>
                            <strong>{{ trim($street.' '.$houseNumber) }}</strong>
                            <small>{{ $postalCode }} {{ $city }} · {{ $region }}</small>
                        </article>
                        <article>
                            <span>{{ __('stations.fields.status') }}</span>
                            <strong>{{ __('stations.statuses.'.$station->status) }}</strong>
                            <small>{{ __('stations.edit.status_unchanged') }}</small>
                        </article>
                        <article>
                            <span>{{ __('stations.fields.source') }}</span>
                            <strong>{{ __('stations.sources.'.$station->source_type) }}</strong>
                            <small>{{ __('stations.edit.source_unchanged') }}</small>
                        </article>
                    </div>
                </section>
            @endif

            @if ($duplicateWarning)
                <div class="merlin-notice is-warning" role="alert">
                    <strong>{{ __('stations.duplicate.heading') }}</strong>
                    <p>{{ __('stations.duplicate.edit_description') }}</p>
                    <label class="merlin-field">
                        <span>{{ __('stations.fields.duplicate_reason') }} *</span>
                        <textarea rows="3" maxlength="500" wire:model="duplicateReason"></textarea>
                        @error('duplicateReason') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                </div>
            @endif

            <div class="merlin-form-actions">
                <div>
                    @if ($wizardStep === 1)
                        <a class="merlin-secondary-button" href="{{ $backUrl }}" wire:navigate>{{ __('stations.actions.cancel') }}</a>
                    @else
                        <button class="merlin-secondary-button" type="button" wire:click="previousWizardStep">← {{ __('stations.actions.previous') }}</button>
                    @endif
                </div>
                @if ($wizardStep < 3)
                    <button class="merlin-primary-button" type="button" wire:click="nextWizardStep" wire:loading.attr="disabled">
                        {{ __('stations.actions.next') }} →
                    </button>
                @else
                    <button class="merlin-primary-button" type="submit" wire:loading.attr="disabled">
                        {{ __('stations.actions.save_changes') }}
                    </button>
                @endif
            </div>
        </form>
    </div>
</x-filament-panels::page>
