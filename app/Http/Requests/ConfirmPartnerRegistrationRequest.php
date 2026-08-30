<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validiert das Passwort erst beim bewussten Abschluss der E-Mail-Bestätigung.
 */
final class ConfirmPartnerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirmation_token' => ['required', 'string', 'max:128'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:128',
                Password::min(12)->letters()->numbers(),
            ],
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
        return [
            'confirmation_token' => __('registration.attributes.confirmation_token'),
            'password' => __('registration.attributes.password'),
        ];
    }
}
