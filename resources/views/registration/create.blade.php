<x-layouts.public :title="__('registration.create.page_title')" :noindex="true">
    <section class="merlin-registration-grid">
        <aside class="merlin-registration-intro">
            <span class="merlin-eyebrow">{{ __('registration.create.eyebrow') }}</span>
            <h1>{{ __('registration.create.title') }}</h1>
            <p>{{ __('registration.create.introduction') }}</p>

            <ul class="merlin-benefit-list">
                <li><span>14</span>{{ __('registration.create.benefit_trial') }}</li>
                <li><span>∞</span>{{ __('registration.create.benefit_stations') }}</li>
                <li><span>DE</span>{{ __('registration.create.benefit_privacy') }}</li>
            </ul>

            <div class="merlin-security-note">
                <strong>{{ __('registration.create.security_title') }}</strong>
                <p>{{ __('registration.create.security_text') }}</p>
            </div>
        </aside>

        <div class="merlin-form-card">
            <ol class="merlin-step-tabs" aria-label="{{ __('registration.steps.label') }}">
                <li class="is-active"><span>1</span>{{ __('registration.steps.partner') }}</li>
                <li><span>2</span>{{ __('registration.steps.email') }}</li>
                <li><span>3</span>{{ __('registration.steps.access') }}</li>
            </ol>

            <div class="merlin-form-heading">
                <span>{{ __('registration.create.form_kicker') }}</span>
                <h2>{{ __('registration.create.form_title') }}</h2>
                <p>{{ __('registration.create.form_text') }}</p>
            </div>

            @if ($errors->any())
                <div class="merlin-error-summary" role="alert" tabindex="-1">
                    <strong>{{ __('registration.validation_summary') }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('registration.store') }}" class="merlin-form">
                @csrf
                <input type="hidden" name="terms_version" value="{{ $termsDocument->version }}">
                <input type="hidden" name="terms_digest" value="{{ $termsDocument->digest }}">
                <input type="hidden" name="privacy_version" value="{{ $privacyDocument->version }}">
                <input type="hidden" name="privacy_digest" value="{{ $privacyDocument->digest }}">

                <fieldset>
                    <legend>{{ __('registration.groups.contact') }}</legend>
                    <div class="merlin-field-grid">
                        <label>
                            <span>{{ __('registration.attributes.first_name') }}</span>
                            <input name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required maxlength="80">
                            @error('first_name') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>{{ __('registration.attributes.last_name') }}</span>
                            <input name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required maxlength="80">
                            @error('last_name') <small class="merlin-field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>

                    <label>
                        <span>{{ __('registration.attributes.email') }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="254">
                        <small>{{ __('registration.help.business_email') }}</small>
                        @error('email') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>
                </fieldset>

                <fieldset>
                    <legend>{{ __('registration.groups.partner') }}</legend>
                    <label>
                        <span>{{ __('registration.attributes.partner_display_name') }}</span>
                        <input name="partner_display_name" value="{{ old('partner_display_name') }}" autocomplete="organization" required maxlength="160">
                        @error('partner_display_name') <small class="merlin-field-error">{{ $message }}</small> @enderror
                    </label>

                    <div class="merlin-choice-grid">
                        @foreach ($tenantTypes as $type)
                            <label class="merlin-choice-card">
                                <input type="radio" name="tenant_type" value="{{ $type->value }}" @checked(old('tenant_type', 'single_operator') === $type->value)>
                                <span>
                                    <strong>{{ __('registration.tenant_types.'.$type->value.'.title') }}</strong>
                                    <small>{{ __('registration.tenant_types.'.$type->value.'.description') }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="merlin-field-grid">
                        <label>
                            <span>{{ __('registration.attributes.country_code') }}</span>
                            <select name="country_code" required>
                                @foreach ($countries as $country)
                                    <option value="{{ $country }}" @selected(old('country_code', 'DE') === $country)>{{ __('registration.countries.'.$country) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>{{ __('registration.attributes.locale') }}</span>
                            <select name="locale" required>
                                @foreach ($locales as $locale)
                                    <option value="{{ $locale }}" @selected(old('locale', 'de') === $locale)>{{ __('registration.locales.'.$locale) }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="merlin-consents">
                    <legend>{{ __('registration.groups.confirmations') }}</legend>
                    <label class="merlin-checkbox">
                        <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required>
                        <span>{!! __('registration.consents.terms', ['url' => route('legal.terms')]) !!}</span>
                    </label>
                    <label class="merlin-checkbox">
                        <input type="checkbox" name="privacy_acknowledged" value="1" @checked(old('privacy_acknowledged')) required>
                        <span>{!! __('registration.consents.privacy', ['url' => route('legal.privacy')]) !!}</span>
                    </label>
                </fieldset>

                <button type="submit" class="merlin-primary-button">
                    {{ __('registration.actions.start') }}
                    <span aria-hidden="true">→</span>
                </button>
                <p class="merlin-submit-note">{{ __('registration.create.submit_note') }}</p>
            </form>
        </div>
    </section>
</x-layouts.public>
