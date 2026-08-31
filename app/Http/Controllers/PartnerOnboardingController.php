<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Http\Requests\StorePartnerOnboardingRequest;
use App\Models\BankDirectoryEntry;
use App\Models\BankDirectoryVersion;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\LegalEntityBankAccount;
use App\Models\Station;
use App\Models\StationContact;
use App\Models\Tenant;
use App\Modules\Banking\Application\GermanIban;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        return view('onboarding.show', compact('context', 'brands'));
    }

    /**
     * Speichert Gesellschaft, Station, Kontakt und optionale Bankverbindung atomar.
     */
    public function store(
        StorePartnerOnboardingRequest $request,
        TenantContext $context,
        GermanIban $ibanService,
        AuditRecorder $auditRecorder,
    ): RedirectResponse {
        abort_unless($context->tenant->status === TenantStatus::Onboarding, 403);
        $data = $request->validated();

        if (filled($data['fuel_station_brand_id'] ?? null)) {
            $brand = FuelStationBrand::query()->whereKey($data['fuel_station_brand_id'])->where('status', 'active')->first();

            if ($brand === null || ! in_array($context->tenant->country_code, $brand->country_codes, true)) {
                return back()->withErrors(['fuel_station_brand_id' => 'Die gewählte Marke ist für dieses Land nicht verfügbar.'])->withInput();
            }
        }

        try {
            $iban = (bool) ($data['add_bank_account'] ?? false)
                ? (filled($data['iban'] ?? null)
                    ? $ibanService->normalizeAndValidate($data['iban'])
                    : $ibanService->calculate($data['bank_code'], $data['account_number']))
                : null;
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['iban' => $exception->getMessage()])->withInput();
        }

        DB::transaction(function () use ($context, $data, $iban, $ibanService, $auditRecorder, $request): void {
            $tenantId = $context->id();
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            // Die Sperre verhindert doppelte Gesellschaften und Stationen bei parallelem
            // Doppelklick. Nur der erste Request darf den Onboardingzustand verbrauchen.
            abort_unless($tenant->status === TenantStatus::Onboarding, 409);

            // `forceCreate` ist hier bewusst lokal: `tenant_id` stammt ausschließlich aus
            // dem geprüften TenantContext und bleibt für allgemeines Mass Assignment gesperrt.
            $legalEntity = LegalEntity::query()->forceCreate([
                'tenant_id' => $tenantId,
                'legal_name' => trim($data['legal_name']),
                'legal_form' => $data['legal_form'],
                'is_primary' => true,
                'status' => 'active',
                'street' => trim($data['billing_street']),
                'house_number' => trim($data['billing_house_number']),
                'address_addition' => $data['billing_address_addition'] ?? null,
                'postal_code' => trim($data['billing_postal_code']),
                'city' => trim($data['billing_city']),
                'region' => trim($data['billing_region']),
                'country_code' => $data['billing_country_code'],
                'billing_email' => mb_strtolower(trim($data['billing_email'])),
                'vat_id' => filled($data['vat_id'] ?? null) ? mb_strtoupper(str_replace(' ', '', $data['vat_id'])) : null,
                'vat_id_masked' => $this->maskIdentifier($data['vat_id'] ?? null),
            ]);

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

    private function maskIdentifier(?string $value): ?string
    {
        $normalized = mb_strtoupper(str_replace(' ', '', (string) $value));

        return $normalized === '' ? null : str_repeat('•', max(0, strlen($normalized) - 4)).substr($normalized, -4);
    }
}
