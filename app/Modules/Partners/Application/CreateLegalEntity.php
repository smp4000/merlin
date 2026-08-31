<?php

namespace App\Modules\Partners\Application;

use App\Enums\LegalEntityStatus;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\LegalEntity;
use App\Models\LegalForm;
use App\Models\Tenant;
use App\Models\TenantBusinessContact;
use App\Modules\Partners\Application\Data\CreateLegalEntityData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Legt eine rechtliche Gesellschaft samt primärem Geschäftskontakt tenant-sicher an.
 *
 * Hauptgesellschaftswechsel und Anlage liegen in derselben Transaktion. Der eindeutige
 * Datenbank-Guard verhindert zusätzlich, dass parallele Requests zwei aktive
 * Hauptgesellschaften desselben Mandanten speichern.
 */
final readonly class CreateLegalEntity
{
    public function __construct(private TenantWriteGuard $writeGuard) {}

    /**
     * Erstellt eine Gesellschaft ausschließlich im aktuell autorisierten Mandanten.
     */
    public function handle(TenantContext $context, CreateLegalEntityData $data): LegalEntity
    {
        $this->writeGuard->ensureBusinessWritesAllowed($context);
        $this->validate($data);

        $legalForm = $data->legalFormId === null ? null : LegalForm::query()->find($data->legalFormId);

        $mustValidateLegalForm = $data->status === LegalEntityStatus::Active || $data->legalFormId !== null;

        if ($mustValidateLegalForm
            && ($legalForm === null || ! filled($data->countryCode) || ! $legalForm->isSelectableFor((string) $data->countryCode))) {
            // Die Meldung unterscheidet absichtlich nicht zwischen unbekannter, inaktiver
            // oder für ein anderes Land bestimmter ID und verhindert Katalog-Sondierung.
            throw ValidationException::withMessages([
                'legal_form' => 'Die gewählte Rechtsform ist für dieses Land nicht verfügbar.',
            ]);
        }

        return DB::transaction(function () use ($context, $data, $legalForm): LegalEntity {
            $tenantId = $context->id();
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            if (filled($data->businessEmail)) {
                $businessContact = TenantBusinessContact::query()
                    ->where('tenant_id', $tenantId)
                    ->first() ?? new TenantBusinessContact;
                $businessContact->tenant_id = $tenantId;
                $businessContact->email = mb_strtolower(trim((string) $data->businessEmail));
                $businessContact->phone = $this->nullableTrim($data->businessPhone);
                $businessContact->fax = $this->nullableTrim($data->businessFax);
                $businessContact->website = $this->nullableTrim($data->website);
                $businessContact->contact_first_name = $this->nullableTrim($data->contactFirstName);
                $businessContact->contact_last_name = $this->nullableTrim($data->contactLastName);
                $businessContact->save();
            }

            $hasActivePrimary = LegalEntity::query()
                ->where('tenant_id', $tenantId)
                ->where('status', LegalEntityStatus::Active)
                ->where('is_primary', true)
                ->exists();
            $makePrimary = $data->status === LegalEntityStatus::Active
                && ($data->makePrimary || ! $hasActivePrimary);

            if ($makePrimary) {
                LegalEntity::query()
                    ->where('tenant_id', $tenantId)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false, 'primary_tenant_guard' => null]);
            }

            $legalEntity = new LegalEntity;
            // Die geschützten Fremdschlüssel stammen ausschließlich aus Context und dem
            // zuvor geprüften globalen Katalog, niemals aus Mass Assignment.
            $legalEntity->tenant_id = $tenantId;
            $legalEntity->legal_form_id = $legalForm?->getKey();
            $legalEntity->legal_form = $legalForm?->key;
            $legalEntity->legal_form_confirmed_at = $legalForm === null ? null : now();
            $legalEntity->legal_name = $this->nullableTrim($data->legalName);
            $legalEntity->trade_name = $this->nullableTrim($data->tradeName);
            $legalEntity->status = $data->status;
            $legalEntity->is_primary = $makePrimary;
            $legalEntity->effective_from = $data->effectiveFrom;
            $legalEntity->street = $this->nullableTrim($data->street);
            $legalEntity->house_number = $this->nullableTrim($data->houseNumber);
            $legalEntity->address_addition = $this->nullableTrim($data->addressAddition);
            $legalEntity->postal_code = $this->nullableTrim($data->postalCode);
            $legalEntity->city = $this->nullableTrim($data->city);
            $legalEntity->region = $this->nullableTrim($data->region);
            $legalEntity->country_code = filled($data->countryCode)
                ? mb_strtoupper(trim((string) $data->countryCode))
                : null;
            $legalEntity->billing_email = filled($data->businessEmail)
                ? mb_strtolower(trim((string) $data->businessEmail))
                : null;
            $legalEntity->postal_street = $this->nullableTrim($data->postalStreet);
            $legalEntity->postal_house_number = $this->nullableTrim($data->postalHouseNumber);
            $legalEntity->postal_address_addition = $this->nullableTrim($data->postalAddressAddition);
            $legalEntity->postal_postal_code = $this->nullableTrim($data->postalPostalCode);
            $legalEntity->postal_city = $this->nullableTrim($data->postalCity);
            $legalEntity->postal_region = $this->nullableTrim($data->postalRegion);
            $legalEntity->postal_country_code = $data->postalCountryCode === null
                ? null
                : mb_strtoupper($data->postalCountryCode);
            $legalEntity->save();

            return $legalEntity->load('legalForm');
        });
    }

    /**
     * Entwürfe dürfen fachlich unvollständig sein; aktive Gesellschaften benötigen die
     * bestätigten Pflichtdaten des Blueprints.
     */
    private function validate(CreateLegalEntityData $data): void
    {
        $required = $data->status === LegalEntityStatus::Active ? 'required' : 'nullable';

        Validator::make([
            'legal_name' => $data->legalName,
            'street' => $data->street,
            'house_number' => $data->houseNumber,
            'postal_code' => $data->postalCode,
            'city' => $data->city,
            'region' => $data->region,
            'country_code' => $data->countryCode,
            'business_email' => $data->businessEmail,
        ], [
            'legal_name' => [$required, 'string', 'max:200'],
            'street' => [$required, 'string', 'max:160'],
            'house_number' => [$required, 'string', 'max:30'],
            'postal_code' => [$required, 'string', 'max:20'],
            'city' => [$required, 'string', 'max:120'],
            'region' => [$required, 'string', 'max:120'],
            'country_code' => [$required, 'string', 'size:2'],
            'business_email' => [$required, 'email:rfc', 'max:254'],
        ])->validate();
    }

    private function nullableTrim(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
