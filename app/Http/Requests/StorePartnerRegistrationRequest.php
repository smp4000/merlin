<?php

namespace App\Http\Requests;

use App\Enums\TenantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validiert ausschließlich die datenarmen Felder der öffentlichen Partnerregistrierung.
 */
final class StorePartnerRegistrationRequest extends FormRequest
{
    /**
     * Die Route ist öffentlich; Missbrauchsschutz erfolgt zusätzlich über Rate Limits.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'partner_display_name' => ['required', 'string', 'max:160'],
            'tenant_type' => ['required', Rule::enum(TenantType::class)],
            'country_code' => ['required', Rule::in(config('merlin.registration.supported_countries'))],
            'locale' => ['required', Rule::in(config('merlin.registration.supported_locales'))],
            'terms_accepted' => ['accepted'],
            'terms_version' => ['required', 'string', 'max:32'],
            'terms_digest' => ['required', 'string', 'size:64'],
            'privacy_acknowledged' => ['accepted'],
            'privacy_version' => ['required', 'string', 'max:32'],
            'privacy_digest' => ['required', 'string', 'size:64'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return __('registration.attributes');
    }

    /**
     * Vereinheitlicht E-Mail, Land und Sprache vor Rate Limit und Validierung.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'country_code' => mb_strtoupper(trim((string) $this->input('country_code'))),
            'locale' => mb_strtolower(trim((string) $this->input('locale'))),
        ]);
    }
}
