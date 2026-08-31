<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validiert ausschließlich das Format einer Betriebsauswahl.
 *
 * Die eigentliche Berechtigung wird anschließend serverseitig über die Membership
 * aufgelöst. Eine manipulierte ULID kann daher keinen fremden Mandanten auswählen.
 */
final class SelectTenantRequest extends FormRequest
{
    /**
     * Die Route ist nur für angemeldete Identitäten bestimmt; die Tenant-Berechtigung
     * wird danach bewusst nicht durch diese Formularautorisierung ersetzt.
     */
    public function authorize(): bool
    {
        return $this->user()?->email_verified_at !== null;
    }

    /**
     * Prüft nur Pflichtfeld und ULID-Format, ohne einen globalen Existenztest auszuführen.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tenant_public_id' => ['required', 'string', 'ulid'],
        ];
    }

    /**
     * Liefert die verständliche deutsche Feldbezeichnung für Validierungsfehler.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['tenant_public_id' => __('merlin.tenant_selection.field')];
    }
}
