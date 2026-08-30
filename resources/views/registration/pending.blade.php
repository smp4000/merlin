<x-layouts.public :title="__('registration.pending.page_title')" :noindex="true">
    <section class="merlin-state-page">
        <div class="merlin-state-card">
            <div class="merlin-state-icon" aria-hidden="true">✉</div>
            <span class="merlin-eyebrow">{{ __('registration.pending.eyebrow') }}</span>
            <h1>{{ __('registration.pending.title') }}</h1>
            <p>{{ __('registration.pending.text') }}</p>
            <div class="merlin-state-hint">
                <strong>{{ __('registration.pending.hint_title') }}</strong>
                <p>{{ __('registration.pending.hint_text', ['minutes' => config('merlin.registration.token_lifetime_minutes')]) }}</p>
            </div>
            <a href="{{ route('filament.admin.auth.login') }}" class="merlin-secondary-button">{{ __('registration.actions.to_login') }}</a>
        </div>
    </section>
</x-layouts.public>
