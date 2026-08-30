<x-layouts.public :title="__('registration.confirm.page_title')" :noindex="true">
    <section class="merlin-state-page">
        <div class="merlin-form-card merlin-confirm-card">
            <ol class="merlin-step-tabs" aria-label="{{ __('registration.steps.label') }}">
                <li class="is-complete"><span>✓</span>{{ __('registration.steps.partner') }}</li>
                <li class="is-complete"><span>✓</span>{{ __('registration.steps.email') }}</li>
                <li class="is-active"><span>3</span>{{ __('registration.steps.access') }}</li>
            </ol>

            <div class="merlin-form-heading">
                <span>{{ __('registration.confirm.eyebrow') }}</span>
                <h1>{{ __('registration.confirm.title') }}</h1>
                <p>{{ __('registration.confirm.text') }}</p>
            </div>

            <div class="merlin-error-summary" role="alert" tabindex="-1" data-validation-errors @if (! $errors->any()) hidden @endif>
                <strong>{{ __('registration.validation_summary') }}</strong>
                <ul data-validation-list>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>

            <form method="POST" action="{{ route('registration.confirm.store', ['intent' => $intentPublicId]) }}" class="merlin-form" data-confirmation-form hidden>
                @csrf
                <input type="hidden" name="confirmation_token" value="" data-confirmation-token>
                <label>
                    <span>{{ __('registration.attributes.password') }}</span>
                    <input type="password" name="password" autocomplete="new-password" required minlength="12" maxlength="128">
                    <small>{{ __('registration.help.password') }}</small>
                </label>
                <label>
                    <span>{{ __('registration.attributes.password_confirmation') }}</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required minlength="12" maxlength="128">
                </label>
                <fieldset class="merlin-consent-group">
                    <legend>{{ __('registration.groups.confirmations') }}</legend>
                    <input type="hidden" name="terms_version" value="{{ $termsDocument->version }}">
                    <input type="hidden" name="terms_digest" value="{{ $termsDocument->digest }}">
                    <label class="merlin-checkbox-row">
                        <input type="checkbox" name="terms_accepted" value="1" required>
                        <span>{!! __('registration.consents.terms', ['url' => route('legal.terms')]) !!}</span>
                    </label>
                    <input type="hidden" name="privacy_version" value="{{ $privacyDocument->version }}">
                    <input type="hidden" name="privacy_digest" value="{{ $privacyDocument->digest }}">
                    <label class="merlin-checkbox-row">
                        <input type="checkbox" name="privacy_acknowledged" value="1" required>
                        <span>{!! __('registration.consents.privacy', ['url' => route('legal.privacy')]) !!}</span>
                    </label>
                </fieldset>
                <button type="submit" class="merlin-primary-button">
                    {{ __('registration.actions.complete') }}
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <div class="merlin-state-hint" data-token-missing>
                <strong>{{ __('registration.confirm.token_missing_title') }}</strong>
                <p>{{ __('registration.confirm.token_missing_text') }}</p>
            </div>

            <script>
                (() => {
                    const fragment = new URLSearchParams(window.location.hash.slice(1));
                    const token = fragment.get('token');
                    const form = document.querySelector('[data-confirmation-form]');
                    const tokenInput = document.querySelector('[data-confirmation-token]');
                    const errorBox = document.querySelector('[data-validation-errors]');
                    const errorList = document.querySelector('[data-validation-list]');
                    window.history.replaceState(null, document.title, window.location.pathname + window.location.search);

                    if (/^[A-Za-z0-9_-]{43}$/.test(token ?? '')) {
                        tokenInput.value = token;
                        form.hidden = false;
                        document.querySelector('[data-token-missing]').hidden = true;
                    }

                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        const submitButton = form.querySelector('button[type="submit"]');
                        submitButton.disabled = true;
                        errorBox.hidden = true;
                        errorList.replaceChildren();

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                body: new FormData(form),
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const result = await response.json();

                            if (response.ok && result.redirect_to) {
                                tokenInput.value = '';
                                window.location.assign(result.redirect_to);
                                return;
                            }

                            const messages = response.status === 422
                                ? Object.values(result.errors ?? {}).flat()
                                : [result.message ?? @json(__('registration.confirm.request_failed'))];

                            for (const message of messages) {
                                const item = document.createElement('li');
                                item.textContent = message;
                                errorList.append(item);
                            }

                            errorBox.hidden = false;
                            errorBox.focus();
                        } catch {
                            const item = document.createElement('li');
                            item.textContent = @json(__('registration.confirm.request_failed'));
                            errorList.append(item);
                            errorBox.hidden = false;
                        } finally {
                            submitButton.disabled = false;
                        }
                    });
                })();
            </script>
        </div>
    </section>
</x-layouts.public>
