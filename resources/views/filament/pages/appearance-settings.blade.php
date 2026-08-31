<x-filament-panels::page>
    <form class="merlin-appearance" wire:submit="save">
        <header class="merlin-appearance__heading">
            <span class="merlin-eyebrow">{{ __('appearance.eyebrow') }}</span>
            <h2>{{ __('appearance.heading') }}</h2>
            <p>{{ __('appearance.description') }}</p>
        </header>

        <fieldset class="merlin-palette-grid">
            <legend class="sr-only">{{ __('appearance.choose') }}</legend>
            @foreach ($palettes as $key => $palette)
                <label class="merlin-palette-option" wire:key="palette-{{ $key }}"
                       style="--preview-accent: {{ $palette['variables']['accent'] }}; --preview-soft: {{ $palette['variables']['accent_soft'] }}">
                    <input type="radio" wire:model.live="selectedTheme" value="{{ $key }}">
                    <span class="merlin-palette-option__swatch" aria-hidden="true"></span>
                    <strong>{{ $palette['label'] }}</strong>
                    <small>{{ __('appearance.palette_hint') }}</small>
                </label>
            @endforeach
        </fieldset>
        @error('selectedTheme') <p class="merlin-field-error">{{ $message }}</p> @enderror

        @php($preview = $palettes[$selectedTheme] ?? $palettes[\App\Enums\ThemePalette::default()->value])
        <section class="merlin-theme-preview"
                 style="--merlin-accent: {{ $preview['variables']['accent'] }}; --merlin-accent-hover: {{ $preview['variables']['accent_hover'] }}; --merlin-accent-soft: {{ $preview['variables']['accent_soft'] }}; --merlin-accent-focus: {{ $preview['variables']['accent_focus'] }}"
                 aria-labelledby="theme-preview-heading">
            <div>
                <span class="merlin-eyebrow">{{ __('appearance.preview.eyebrow') }}</span>
                <h3 id="theme-preview-heading">{{ __('appearance.preview.heading') }}</h3>
                <p>{{ __('appearance.preview.description') }}</p>
            </div>
            <button type="button" class="merlin-primary-button">{{ __('appearance.preview.button') }}</button>
            <label class="merlin-field">
                <span>{{ __('appearance.preview.field') }}</span>
                <input type="text" value="{{ __('appearance.preview.value') }}" readonly>
            </label>
        </section>

        <div class="merlin-form-actions">
            <button type="button" class="merlin-secondary-button" wire:click="$set('selectedTheme', '{{ \App\Enums\ThemePalette::default()->value }}')">
                {{ __('appearance.reset') }}
            </button>
            <button type="submit" class="merlin-primary-button" wire:loading.attr="disabled">
                {{ __('appearance.save') }}
            </button>
        </div>
    </form>
</x-filament-panels::page>
