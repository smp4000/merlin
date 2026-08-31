<?php

namespace App\Http\Controllers;

use App\Enums\LegalEntityIdentifierType;
use App\Enums\LegalEntityStatus;
use App\Enums\TenantStatus;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Http\Requests\StorePartnerOnboardingRequest;
use App\Models\BankDirectoryEntry;
use App\Models\BankDirectoryVersion;
use App\Models\FuelStationBrand;
use App\Models\LegalEntityBankAccount;
use App\Models\LegalForm;
use App\Models\Station;
use App\Models\StationContact;
use App\Models\Tenant;
use App\Modules\Banking\Application\GermanIban;
use App\Modules\Partners\Application\CreateLegalEntity;
use App\Modules\Partners\Application\Data\CreateLegalEntityData;
use App\Modules\Partners\Application\StoreLegalEntityIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Führt den bestätigten Partner durch die erste geschützte Stammdatenerfassung.
 *
 * Der Controller akzeptiert keine `tenant_id`. Jede Abfrage und Speicherung wird aus der
 * aktiven Sitzung und der wirksamen Membership abgeleitet, damit manipulierte Formularwerte
 * keine Mandantengrenze überschreiten können.
 */
final class PartnerOnboardingController extends Controller
{
    public function show(TenantContext $context): View|RedirectResponse
    {
        if ($context->tenant->status !== TenantStatus::Onboarding) {
            return redirect('/admin/dashboard');
        }

        $brands = FuelStationBrand::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn (FuelStationBrand $brand): bool => in_array($context->tenant->country_code, $brand->country_codes, true));

        $legalForms = LegalForm::query()
            ->where('status', 'active')
            ->get()
            ->filter(fn (LegalForm $legalForm): bool => $legalForm->isSelectableFor($context->tenant->country_code))
            ->sortBy(fn (LegalForm $legalForm): string => $legalForm->label($context->tenant->default_locale));

        return view('onboarding.show', compact('context', 'brands', 'legalForms'));
    }

    /**
     * Speichert Gesellschaft, Station, Kontakt und optionale Bankverbindung atomar.
     */
    public function store(
        StorePartnerOnboardingRequest $request,
        TenantContext $context,
        GermanIban $ibanService,
        AuditRecorder $auditRecorder,
        CreateLegalEntity $createLegalEntity,
        StoreLegalEntityIdentifier $storeIdentifier,
    ): RedirectResponse {
        abort_unless($context->tenant->status === TenantStatus::Onboarding, 403);
        $data = $request->validated();

        if (filled($data['fuel_station_brand_id'] ?? null)) {
            $brand = FuelStationBrand::query()->whereKey($data['fuel_station_brand_id'])->where('status', 'active')->first();

            if ($brand === null || ! in_array($context->tenant->country_code, $brand->country_codes, true)) {
                return back()
                    ->withErrors(['fuel_station_brand_id' => 'Die gewählte Marke ist für dieses Land nicht verfügbar.'])
                    ->withInput($request->except(['vat_id', 'iban', 'account_number']));
            }
        }

        try {
            $iban = (bool) ($data['add_bank_account'] ?? false)
                ? (filled($data['iban'] ?? null)
                    ? $ibanService->normalizeAndValidate($data['iban'])
                    : $ibanService->calculate($data['bank_code'], $data['account_number']))
                : null;
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['iban' => $exception->getMessage()])
                ->withInput($request->except(['vat_id', 'iban', 'account_number']));
        }

        DB::transaction(function () use ($context, $data, $iban, $ibanService, $auditRecorder, $request, $createLegalEntity, $storeIdentifier): void {
            $tenantId = $context->id();
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            // Die Sperre verhindert doppelte Gesellschaften und Stationen bei parallelem
            // Doppelklick. Nur der erste Request darf den Onboardingzustand verbrauchen.
            abort_unless($tenant->status === TenantStatus::Onboarding, 409);

            $legalForm = LegalForm::query()->where('key', $data['legal_form'])->first();

            if ($legalForm === null) {
                throw ValidationException::withMessages([
                    'legal_form' => 'Die gewählte Rechtsform ist nicht verfügbar.',
                ]);
            }

            $legalEntity = $createLegalEntity->handle($context, new CreateLegalEntityData(
                legalFormId: (int) $legalForm->getKey(),
                legalName: $data['legal_name'],
                tradeName: null,
                status: LegalEntityStatus::Active,
                makePrimary: true,
                street: $data['billing_street'],
                houseNumber: $data['billing_house_number'],
                addressAddition: $data['billing_address_addition'] ?? null,
                postalCode: $data['billing_postal_code'],
                city: $data['billing_city'],
                region: $data['billing_region'],
                countryCode: $data['billing_country_code'],
                businessEmail: $data['billing_email'],
            ));

            if (filled($data['vat_id'] ?? null)) {
                $storeIdentifier->handle(
                    $context,
                    $legalEntity->public_id,
                    LegalEntityIdentifierType::VatId,
                    $data['billing_country_code'],
                    $data['vat_id'],
                );
            }

            $station = Station::query()->forceCreate([
                'tenant_id' => $tenantId,
                'legal_entity_id' => $legalEntity->getKey(),
                'fuel_station_brand_id' => $data['fuel_station_brand_id'] ?? null,
                'name' => trim($data['station_name']),
                'status' => 'active',
                'street' => trim($data['station_street']),
                'house_number' => trim($data['station_house_number']),
                'address_addition' => $data['station_address_addition'] ?? null,
                'postal_code' => trim($data['station_postal_code']),
                'city' => trim($data['station_city']),
                'region' => trim($data['station_region']),
                'country_code' => $data['station_country_code'],
                'timezone' => $tenant->timezone,
            ]);

            StationContact::query()->forceCreate([
                'tenant_id' => $tenantId,
                'station_id' => $station->getKey(),
                'salutation' => $data['manager_salutation'],
                'first_name' => trim($data['manager_first_name']),
                'last_name' => trim($data['manager_last_name']),
                'email' => mb_strtolower(trim($data['manager_email'])),
                'phone' => trim($data['manager_phone']),
                'fax' => $data['manager_fax'] ?? null,
                'is_station_manager' => true,
            ]);

            if ($iban !== null) {
                $bankCode = $ibanService->bankCode($iban);
                $version = BankDirectoryVersion::query()->where('status', 'active')->latest('activated_at')->first();
                $bank = $version === null ? null : BankDirectoryEntry::query()
                    ->where('bank_directory_version_id', $version->getKey())
                    ->where('bank_code', $bankCode)
                    ->where('leading_institution', '1')
                    ->where('change_indicator', '!=', 'D')
                    ->first();

                LegalEntityBankAccount::query()->forceCreate([
                    'tenant_id' => $tenantId,
                    'legal_entity_id' => $legalEntity->getKey(),
                    'account_holder' => trim($data['account_holder']),
                    'iban' => $iban,
                    'iban_masked' => 'DE•• •••• •••• •••• ••'.substr($iban, -4),
                    'iban_fingerprint' => hash_hmac('sha256', $tenantId.'|'.$iban, (string) config('app.key')),
                    'bank_code' => $bankCode,
                    'bank_name' => $bank?->name,
                    'bic' => $bank?->bic,
                    'validation_status' => 'format_and_checksum_valid',
                    'status' => 'active',
                    'bank_directory_version_id' => $version?->getKey(),
                ]);
            }

            $tenant->status = TenantStatus::Active;
            $tenant->onboarding_completed_at = now();
            $tenant->save();

            $auditRecorder->record(
                'tenant.onboarding.completed',
                'tenant',
                $tenant->public_id,
                (string) Str::uuid(),
                ['station_public_id' => $station->public_id, 'bank_account_added' => $iban !== null],
                $tenant,
                $request->user(),
            );
        });

        return redirect('/admin/dashboard')->with('status', 'Onboarding erfolgreich abgeschlossen.');
    }
}
