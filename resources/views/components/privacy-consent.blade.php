<div
    class="merlin-privacy"
    data-privacy-consent
    data-consent-version="1"
    data-saved-message="{{ __('privacy.status') }}"
    hidden
>
    <div class="merlin-privacy-backdrop" aria-hidden="true"></div>

    <section
        class="merlin-privacy-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="merlin-privacy-title"
        aria-describedby="merlin-privacy-description"
        tabindex="-1"
    >
        <button
            type="button"
            class="merlin-privacy-close"
            data-privacy-close
            aria-label="{{ __('privacy.actions.close') }}"
            hidden
        >
            <span aria-hidden="true">×</span>
        </button>

        <header class="merlin-privacy-heading">
            <span class="merlin-privacy-icon" aria-hidden="true">✓</span>
            <div>
                <span class="merlin-eyebrow">{{ __('privacy.eyebrow') }}</span>
                <h2 id="merlin-privacy-title">{{ __('privacy.title') }}</h2>
                <p id="merlin-privacy-description">{{ __('privacy.introduction') }}</p>
                <a href="{{ route('legal.privacy') }}">
                    {{ __('privacy.details') }} <span aria-hidden="true">→</span>
                </a>
            </div>
        </header>

        <div class="merlin-privacy-categories">
            <label class="merlin-privacy-category is-required">
                <input type="checkbox" checked disabled>
                <span>
                    <strong>{{ __('privacy.categories.necessary.title') }}</strong>
                    <small>{{ __('privacy.categories.necessary.description') }}</small>
                </span>
            </label>

            <label class="merlin-privacy-category">
                <input type="checkbox" name="privacy_analytics" data-privacy-analytics>
                <span>
                    <strong>{{ __('privacy.categories.analytics.title') }}</strong>
                    <small>{{ __('privacy.categories.analytics.description') }}</small>
                </span>
            </label>

            <label class="merlin-privacy-category">
                <input type="checkbox" name="privacy_external_media" data-privacy-external-media>
                <span>
                    <strong>{{ __('privacy.categories.external_media.title') }}</strong>
                    <small>{{ __('privacy.categories.external_media.description') }}</small>
                </span>
            </label>
        </div>

        <footer class="merlin-privacy-actions">
            <p class="sr-only" role="status" aria-live="polite" data-privacy-status></p>
            <button type="button" class="merlin-privacy-button is-primary" data-privacy-necessary>
                {{ __('privacy.actions.necessary_only') }}
            </button>
            <button type="button" class="merlin-privacy-button is-secondary" data-privacy-save>
                {{ __('privacy.actions.save') }}
            </button>
            <button type="button" class="merlin-privacy-button is-primary" data-privacy-accept-all>
                {{ __('privacy.actions.accept_all') }}
            </button>
        </footer>
    </section>
</div>
