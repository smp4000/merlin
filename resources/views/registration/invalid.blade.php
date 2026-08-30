<x-layouts.public :title="__('registration.invalid.page_title')" :noindex="true">
    <section class="merlin-state-page">
        <div class="merlin-state-card">
            <div class="merlin-state-icon is-warning" aria-hidden="true">!</div>
            <span class="merlin-eyebrow">{{ __('registration.invalid.eyebrow') }}</span>
            <h1>{{ __('registration.invalid.title') }}</h1>
            <p>{{ __('registration.invalid.text') }}</p>
            <a href="{{ route('registration.create') }}" class="merlin-primary-button">{{ __('registration.actions.restart') }}</a>
        </div>
    </section>
</x-layouts.public>
