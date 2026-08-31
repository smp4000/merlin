<?php

use App\Enums\LegalEntityIdentifierStatus;
use App\Enums\LegalEntityIdentifierType;
use App\Enums\LegalEntityStatus;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Führt das belastbare Gesellschaftsmodell ein und übernimmt vorhandene Pilotdaten.
     *
     * Vertrauliche Altwerte werden ausschließlich im Arbeitsspeicher entschlüsselt. Weder
     * die Migration noch Fehlerpfade geben Steuerkennungen oder ihre Fingerprints aus.
     */
    public function up(): void
    {
        Schema::create('legal_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('labels');
            $table->json('country_codes');
            $table->string('status', 24)->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['status', 'valid_until']);
        });

        $now = now();
        DB::table('legal_forms')->insert(array_map(
            static fn (array $form): array => $form + ['status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            $this->migrationLegalForms(),
        ));

        Schema::table('legal_entities', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id');
            $table->foreignId('legal_form_id')->nullable()->after('legal_form')->constrained('legal_forms')->restrictOnDelete();
            $table->string('trade_name', 200)->nullable()->after('legal_name');
            $table->date('effective_from')->nullable()->after('status');
            $table->string('postal_street', 160)->nullable()->after('country_code');
            $table->string('postal_house_number', 30)->nullable()->after('postal_street');
            $table->string('postal_address_addition', 120)->nullable()->after('postal_house_number');
            $table->string('postal_postal_code', 20)->nullable()->after('postal_address_addition');
            $table->string('postal_city', 120)->nullable()->after('postal_postal_code');
            $table->string('postal_region', 120)->nullable()->after('postal_city');
            $table->char('postal_country_code', 2)->nullable()->after('postal_region');
            $table->string('legacy_legal_form_label', 160)->nullable()->after('legal_form_id');
            $table->dateTime('legal_form_confirmed_at')->nullable()->after('legacy_legal_form_label');
            $table->unsignedBigInteger('primary_tenant_guard')->nullable()->after('is_primary');

            // Der Guard wird ausschließlich für aktive Hauptgesellschaften gesetzt. Der
            // eindeutige Index verhindert auch bei konkurrierenden Requests eine zweite.
            $table->unique('public_id', 'legal_entities_public_id_unique');
            $table->unique(['tenant_id', 'id'], 'legal_entities_tenant_id_unique');
            $table->unique('primary_tenant_guard', 'legal_entities_primary_tenant_unique');
        });

        Schema::create('tenant_business_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->restrictOnDelete();
            $table->string('email', 254);
            $table->string('phone', 40)->nullable();
            $table->string('fax', 40)->nullable();
            $table->string('website', 2048)->nullable();
            $table->string('contact_first_name', 80)->nullable();
            $table->string('contact_last_name', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('legal_entity_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('legal_entity_id');
            $table->string('type', 40);
            $table->char('country_code', 2);
            $table->text('value');
            $table->string('value_masked', 80);
            $table->char('fingerprint', 64);
            $table->json('metadata')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamps();

            // Der zusammengesetzte Fremdschlüssel macht eine Gesellschaft eines anderen
            // Mandanten bereits auf Datenbankebene als Ziel unmöglich.
            $table->foreign(['tenant_id', 'legal_entity_id'], 'identifier_tenant_entity_foreign')
                ->references(['tenant_id', 'id'])
                ->on('legal_entities')
                ->restrictOnDelete();
            $table->unique(['tenant_id', 'type', 'country_code', 'fingerprint'], 'identifier_tenant_value_unique');
            $table->index(['tenant_id', 'legal_entity_id', 'status'], 'identifier_entity_status_index');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dateTime('onboarding_completed_at')->nullable()->after('timezone');
        });

        $this->migrateExistingLegalEntities();
    }

    /**
     * Stellt bei einem Rollback die alten kompatiblen Felder wieder her, bevor die neuen
     * Tabellen entfernt werden. Dadurch ist auch der Rückweg für Pilotdaten verlustfrei.
     */
    public function down(): void
    {
        DB::table('legal_entities')->orderBy('id')->each(function (object $entity): void {
            $formKey = $entity->legal_form_id === null
                ? null
                : DB::table('legal_forms')->where('id', $entity->legal_form_id)->value('key');

            $legacyForm = $entity->legacy_legal_form_label
                ?: $this->legacyFormValue(is_string($formKey) ? $formKey : null)
                ?: $entity->legal_form;

            $vatIdentifier = DB::table('legal_entity_identifiers')
                ->where('tenant_id', $entity->tenant_id)
                ->where('legal_entity_id', $entity->id)
                ->where('type', LegalEntityIdentifierType::VatId->value)
                ->orderByDesc('id')
                ->first();

            DB::table('legal_entities')->where('id', $entity->id)->update([
                'legal_form' => $legacyForm,
                // Der Ciphertext kann ohne Klartextkontakt in den alten verschlüsselten Cast
                // zurückgeschrieben werden.
                'vat_id' => $vatIdentifier?->value,
                'vat_id_masked' => $vatIdentifier?->value_masked,
            ]);
        });

        Schema::dropIfExists('legal_entity_identifiers');
        Schema::dropIfExists('tenant_business_contacts');

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('onboarding_completed_at');
        });

        Schema::table('legal_entities', function (Blueprint $table): void {
            $table->dropForeign(['legal_form_id']);
            $table->dropUnique('legal_entities_public_id_unique');
            $table->dropUnique('legal_entities_tenant_id_unique');
            $table->dropUnique('legal_entities_primary_tenant_unique');
            $table->dropColumn([
                'public_id',
                'legal_form_id',
                'trade_name',
                'effective_from',
                'postal_street',
                'postal_house_number',
                'postal_address_addition',
                'postal_postal_code',
                'postal_city',
                'postal_region',
                'postal_country_code',
                'legacy_legal_form_label',
                'legal_form_confirmed_at',
                'primary_tenant_guard',
            ]);
        });

        Schema::dropIfExists('legal_forms');
    }

    /**
     * Überführt Rechtsform, Kontakt, Hauptgesellschaft und optionale USt-ID ohne Ausgabe
     * vertraulicher Werte. Nicht bekannte Rechtsformen bleiben explizit als Legacytext.
     */
    private function migrateExistingLegalEntities(): void
    {
        $formIds = DB::table('legal_forms')->pluck('id', 'key');
        $primaryEntityIds = DB::table('legal_entities')
            ->where('status', LegalEntityStatus::Active->value)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn ($entities): int => (int) $entities->first()->id);

        DB::table('legal_entities')->orderBy('id')->each(function (object $entity) use ($formIds, $primaryEntityIds): void {
            $countryCode = mb_strtoupper((string) $entity->country_code);
            $mappedKey = $this->mapLegacyForm((string) $entity->legal_form, $countryCode);
            $formId = $mappedKey === null ? null : $formIds->get($mappedKey);
            // Selbst inkonsistente Pilotdaten mit null oder mehreren Markierungen werden
            // deterministisch auf genau eine aktive Hauptgesellschaft normalisiert.
            $isActivePrimary = $primaryEntityIds->get($entity->tenant_id) === (int) $entity->id;

            DB::table('legal_entities')->where('id', $entity->id)->update([
                'public_id' => (string) Str::ulid(),
                'legal_form_id' => $formId,
                'legacy_legal_form_label' => $formId === null ? (string) $entity->legal_form : null,
                'legal_form_confirmed_at' => $formId === null ? null : now(),
                'is_primary' => $isActivePrimary,
                'primary_tenant_guard' => $isActivePrimary ? $entity->tenant_id : null,
            ]);

            DB::table('tenant_business_contacts')->updateOrInsert(
                ['tenant_id' => $entity->tenant_id],
                [
                    'email' => mb_strtolower(trim((string) $entity->billing_email)),
                    'created_at' => $entity->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );

            $this->migrateVatIdentifier($entity);
        });

        // Aktive Pilotmandanten mit abgeschlossener Ersteinrichtung erhalten einen
        // nachvollziehbaren Onboardingzeitpunkt, ohne ihren Lifecycle neu zu starten.
        DB::table('tenants')
            ->where('status', '!=', 'onboarding')
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('legal_entities')
                ->whereColumn('legal_entities.tenant_id', 'tenants.id'))
            ->update(['onboarding_completed_at' => now()]);
    }

    /**
     * Verschiebt eine vorhandene verschlüsselte USt-ID in das typisierte Modell.
     * Bei nicht entschlüsselbaren Altdaten bleibt das Ursprungsfeld unangetastet.
     */
    private function migrateVatIdentifier(object $entity): void
    {
        if (! is_string($entity->vat_id) || $entity->vat_id === '') {
            return;
        }

        try {
            $plainValue = Crypt::decryptString($entity->vat_id);
        } catch (DecryptException) {
            return;
        }

        $normalized = mb_strtoupper((string) preg_replace('/[\s\-\.]+/u', '', $plainValue));

        if ($normalized === '') {
            return;
        }

        DB::table('legal_entity_identifiers')->insert([
            'tenant_id' => $entity->tenant_id,
            'legal_entity_id' => $entity->id,
            'type' => LegalEntityIdentifierType::VatId->value,
            'country_code' => mb_strtoupper((string) $entity->country_code),
            'value' => Crypt::encryptString($normalized),
            'value_masked' => $entity->vat_id_masked ?: $this->mask($normalized),
            'fingerprint' => hash_hmac(
                'sha256',
                $entity->tenant_id.'|vat_id|'.mb_strtoupper((string) $entity->country_code).'|'.$normalized,
                (string) config('app.key'),
            ),
            'status' => LegalEntityIdentifierStatus::Active->value,
            'created_at' => $entity->created_at ?? now(),
            'updated_at' => now(),
        ]);

        DB::table('legal_entities')->where('id', $entity->id)->update([
            'vat_id' => null,
            'vat_id_masked' => null,
        ]);
    }

    /** @return list<array{key: string, labels: string, country_codes: string}> */
    private function migrationLegalForms(): array
    {
        $definitions = [
            ['de_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'DE'],
            ['de_registered_merchant', 'Eingetragener Kaufmann (e. K.)', 'Registered merchant', 'DE'],
            ['de_gbr', 'Gesellschaft bürgerlichen Rechts (GbR)', 'Civil-law partnership', 'DE'],
            ['de_ohg', 'Offene Handelsgesellschaft (OHG)', 'General partnership', 'DE'],
            ['de_kg', 'Kommanditgesellschaft (KG)', 'Limited partnership', 'DE'],
            ['de_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH)', 'Limited liability company', 'DE'],
            ['de_ug_limited', 'Unternehmergesellschaft (haftungsbeschränkt)', 'Entrepreneurial company', 'DE'],
            ['de_gmbh_co_kg', 'GmbH & Co. KG', 'GmbH & Co. KG', 'DE'],
            ['de_ag', 'Aktiengesellschaft (AG)', 'Stock corporation', 'DE'],
            ['at_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'AT'],
            ['at_registered_merchant', 'Eingetragenes Unternehmen (e.U.)', 'Registered sole trader', 'AT'],
            ['at_gesbr', 'Gesellschaft bürgerlichen Rechts (GesbR)', 'Civil-law partnership', 'AT'],
            ['at_og', 'Offene Gesellschaft (OG)', 'General partnership', 'AT'],
            ['at_kg', 'Kommanditgesellschaft (KG)', 'Limited partnership', 'AT'],
            ['at_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH)', 'Limited liability company', 'AT'],
            ['at_ag', 'Aktiengesellschaft (AG)', 'Stock corporation', 'AT'],
            ['ch_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'CH'],
            ['ch_general_partnership', 'Kollektivgesellschaft', 'General partnership', 'CH'],
            ['ch_limited_partnership', 'Kommanditgesellschaft', 'Limited partnership', 'CH'],
            ['ch_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH/Sàrl/Sagl)', 'Limited liability company', 'CH'],
            ['ch_ag', 'Aktiengesellschaft (AG/SA)', 'Stock corporation', 'CH'],
        ];

        return array_map(static fn (array $definition): array => [
            'key' => $definition[0],
            'labels' => json_encode(['de' => $definition[1], 'en' => $definition[2]], JSON_THROW_ON_ERROR),
            'country_codes' => json_encode([$definition[3]], JSON_THROW_ON_ERROR),
        ], $definitions);
    }

    private function mapLegacyForm(string $legacyValue, string $countryCode): ?string
    {
        $legacyValue = mb_strtolower(trim($legacyValue));

        return match ($countryCode) {
            'DE' => match ($legacyValue) {
                'sole_proprietorship' => 'de_sole_proprietorship',
                'gbr' => 'de_gbr',
                'ug' => 'de_ug_limited',
                'gmbh' => 'de_gmbh',
                'ag' => 'de_ag',
                'kg' => 'de_kg',
                'gmbh_co_kg' => 'de_gmbh_co_kg',
                default => null,
            },
            'AT' => match ($legacyValue) {
                'sole_proprietorship' => 'at_sole_proprietorship',
                'gmbh' => 'at_gmbh',
                'ag' => 'at_ag',
                'kg' => 'at_kg',
                default => null,
            },
            'CH' => match ($legacyValue) {
                'sole_proprietorship' => 'ch_sole_proprietorship',
                'gmbh' => 'ch_gmbh',
                'ag' => 'ch_ag',
                'kg' => 'ch_limited_partnership',
                default => null,
            },
            default => null,
        };
    }

    private function legacyFormValue(?string $formKey): ?string
    {
        return match ($formKey) {
            'de_sole_proprietorship', 'at_sole_proprietorship', 'ch_sole_proprietorship' => 'sole_proprietorship',
            'de_gbr' => 'gbr',
            'de_ug_limited' => 'ug',
            'de_gmbh', 'at_gmbh', 'ch_gmbh' => 'gmbh',
            'de_ag', 'at_ag', 'ch_ag' => 'ag',
            'de_kg', 'at_kg', 'ch_limited_partnership' => 'kg',
            'de_gmbh_co_kg' => 'gmbh_co_kg',
            default => null,
        };
    }

    private function mask(string $value): string
    {
        return str_repeat('•', max(0, mb_strlen($value) - 4)).mb_substr($value, -4);
    }
};
