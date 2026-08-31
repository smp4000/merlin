<x-layouts.onboarding
    :title="__('merlin.tenant_selection.page_title')"
    :tagline="__('merlin.tenant_selection.brand_tagline')"
>
    <section class="merlin-tenant-selection" aria-labelledby="tenant-selection-heading">
        <div class="merlin-tenant-selection__heading">
            <span class="merlin-eyebrow">{{ __('merlin.tenant_selection.eyebrow') }}</span>
            <h1 id="tenant-selection-heading">{{ __('merlin.tenant_selection.title') }}</h1>
            <p>{{ __('merlin.tenant_selection.introduction') }}</p>
        </div>

        <form method="POST" action="{{ route('tenant-selection.store') }}" class="merlin-tenant-selection__card">
            @csrf

            @if ($errors->has('tenant_public_id'))
                <div class="merlin-error-summary" role="alert">
                    <strong>{{ __('merlin.tenant_selection.error_title') }}</strong>
                    <p>{{ $errors->first('tenant_public_id') }}</p>
                </div>
            @endif

            <fieldset>
                <legend class="sr-only">{{ __('merlin.tenant_selection.field') }}</legend>
                <div class="merlin-tenant-options">
                    @foreach ($memberships as $membership)
                        @php($tenant = $membership->tenant)
                        <label class="merlin-tenant-option">
                            <input
                                type="radio"
                                name="tenant_public_id"
                                value="{{ $tenant->public_id }}"
                                @checked(old('tenant_public_id', $selectedTenantPublicId) === $tenant->public_id || ($memberships->count() === 1 && old('tenant_public_id') === null))
                                required
                            >
                            <span class="merlin-tenant-option__body">
                                <span class="merlin-tenant-option__mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($tenant->display_name, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $tenant->display_name }}</strong>
                                    <small>{{ __('merlin.tenant_selection.partner_number', ['number' => $tenant->public_id]) }}</small>
                                </span>
                                <span class="merlin-tenant-option__check" aria-hidden="true">✓</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="merlin-tenant-selection__actions">
                <p>{{ __('merlin.tenant_selection.security_note') }}</p>
                <button type="submit" class="merlin-primary-button">
                    {{ __('merlin.tenant_selection.continue') }}
                </button>
            </div>
        </form>
    </section>
</x-layouts.onboarding>
