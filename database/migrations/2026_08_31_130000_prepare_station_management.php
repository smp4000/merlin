<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erweitert die Onboarding-Stationen verlustfrei zur mandantenfähigen Verwaltung.
     *
     * Bereits vorhandene Stationen werden als Onboarding-Quelle gekennzeichnet. Externe
     * Referenzen liegen in einer eigenen tenantgebundenen Tabelle, damit eine manipulierte
     * Anbieterkennung weder den Mandanten wechseln noch unbemerkt doppelt importiert werden kann.
     */
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->string('short_name', 80)->nullable()->after('name');
            $table->decimal('latitude', 10, 7)->nullable()->after('country_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('default_locale', 10)->default('de')->after('timezone');
            $table->string('source_type', 32)->default('onboarding')->after('default_locale');
            $table->dateTime('source_verified_at')->nullable()->after('source_type');
            $table->dateTime('source_checked_by_user_at')->nullable()->after('source_verified_at');
            $table->dateTime('activated_at')->nullable()->after('source_checked_by_user_at');
            $table->dateTime('closed_at')->nullable()->after('activated_at');
            $table->unique(['tenant_id', 'id'], 'stations_tenant_id_unique');
            // Zusätzlich zum einzelnen Fremdschlüssel verhindert diese Relation bereits
            // in MySQL, dass eine Station auf eine Gesellschaft eines anderen Tenants zeigt.
            $table->foreign(['tenant_id', 'legal_entity_id'], 'station_tenant_entity_foreign')
                ->references(['tenant_id', 'id'])
                ->on('legal_entities')
                ->restrictOnDelete();
        });

        // Gleichnamige Stationen sind fachlich möglich. Eindeutigkeit entsteht über die
        // externe Quellenreferenz beziehungsweise eine bewusste Dublettenprüfung.
        Schema::table('stations', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'name']);
        });

        Schema::create('station_source_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('station_id');
            $table->string('provider_key', 80);
            $table->text('external_station_id');
            $table->char('external_station_id_hash', 64);
            $table->char('payload_checksum', 64)->nullable();
            $table->dateTime('imported_at');
            $table->dateTime('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign(['tenant_id', 'station_id'], 'station_source_tenant_station_foreign')
                ->references(['tenant_id', 'id'])
                ->on('stations')
                ->cascadeOnDelete();
            $table->unique(
                ['tenant_id', 'provider_key', 'external_station_id_hash'],
                'station_source_tenant_provider_external_unique',
            );
            $table->index(['tenant_id', 'station_id'], 'station_source_tenant_station_index');
        });

        DB::table('stations')->whereNull('activated_at')->update([
            'source_type' => 'onboarding',
            'source_checked_by_user_at' => DB::raw('updated_at'),
            'activated_at' => DB::raw("CASE WHEN status = 'active' THEN updated_at ELSE NULL END"),
        ]);
    }

    /** Entfernt nur die neuen Verwaltungsfelder; bestehende Stammdaten bleiben erhalten. */
    public function down(): void
    {
        Schema::dropIfExists('station_source_references');

        Schema::table('stations', function (Blueprint $table): void {
            $table->dropForeign('station_tenant_entity_foreign');
            $table->unique(['tenant_id', 'name']);
            $table->dropUnique('stations_tenant_id_unique');
            $table->dropColumn([
                'short_name', 'latitude', 'longitude', 'default_locale', 'source_type',
                'source_verified_at', 'source_checked_by_user_at', 'activated_at', 'closed_at',
            ]);
        });
    }
};
