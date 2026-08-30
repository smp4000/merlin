<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt die erste geschützte Onboarding-Stufe samt globalen Bank- und Brandkatalogen.
     *
     * Jede fachliche Tabelle unterhalb eines Partners trägt eine verpflichtende
     * `tenant_id`. Globale Kataloge bleiben davon getrennt und gewähren keinen Zugriff auf
     * Bankverbindungen oder sonstige Inhalte eines Mandanten.
     */
    public function up(): void
    {
        Schema::create('fuel_station_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 160);
            $table->json('country_codes');
            $table->string('status', 24)->default('active');
            $table->timestamps();
        });

        Schema::create('legal_entities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('legal_name', 200);
            $table->string('legal_form', 80);
            $table->boolean('is_primary')->default(true);
            $table->string('status', 24)->default('active');
            $table->string('street', 160);
            $table->string('house_number', 30);
            $table->string('address_addition', 120)->nullable();
            $table->string('postal_code', 20);
            $table->string('city', 120);
            $table->string('region', 120);
            $table->char('country_code', 2)->default('DE');
            $table->string('billing_email', 254);
            $table->text('vat_id')->nullable();
            $table->string('vat_id_masked', 40)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_primary']);
        });

        Schema::create('stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_entity_id')->constrained()->restrictOnDelete();
            $table->foreignId('fuel_station_brand_id')->nullable()->constrained()->restrictOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('name', 160);
            $table->string('status', 24)->default('active');
            $table->string('street', 160);
            $table->string('house_number', 30);
            $table->string('address_addition', 120)->nullable();
            $table->string('postal_code', 20);
            $table->string('city', 120);
            $table->string('region', 120);
            $table->char('country_code', 2)->default('DE');
            $table->string('timezone', 64)->default('Europe/Berlin');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('station_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('salutation', 20);
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 254);
            $table->string('phone', 40);
            $table->string('fax', 40)->nullable();
            $table->boolean('is_station_manager')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'station_id']);
        });

        Schema::create('bank_directory_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('provider', 80)->default('Deutsche Bundesbank');
            $table->text('url');
            $table->string('allowed_host', 190)->default('www.bundesbank.de');
            $table->string('format', 20)->default('csv');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_checked_at')->nullable();
            $table->dateTime('last_imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_directory_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_directory_source_id')->constrained()->restrictOnDelete();
            $table->char('sha256', 64)->unique();
            $table->string('status', 24);
            $table->unsignedInteger('entry_count')->default(0);
            $table->unsignedInteger('added_count')->default(0);
            $table->unsignedInteger('changed_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->dateTime('imported_at');
            $table->dateTime('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_directory_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_directory_version_id')->constrained()->cascadeOnDelete();
            $table->char('bank_code', 8);
            $table->char('leading_institution', 1);
            $table->string('name', 160);
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('short_name', 80)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('account_check_method', 8)->nullable();
            $table->string('record_number', 12)->nullable();
            $table->char('change_indicator', 1)->nullable();
            $table->boolean('deletion_announced')->default(false);
            $table->char('successor_bank_code', 8)->nullable();
            $table->timestamps();

            $table->index(['bank_code', 'leading_institution']);
            $table->unique(['bank_directory_version_id', 'record_number'], 'bank_version_record_unique');
        });

        Schema::create('legal_entity_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_entity_id')->constrained()->restrictOnDelete();
            $table->string('account_holder', 200);
            $table->text('iban');
            $table->string('iban_masked', 40);
            $table->char('iban_fingerprint', 64);
            $table->char('bank_code', 8);
            $table->string('bank_name', 160)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('validation_status', 32)->default('format_and_checksum_valid');
            $table->string('status', 24)->default('active');
            $table->foreignId('bank_directory_version_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'iban_fingerprint'], 'tenant_iban_fingerprint_unique');
        });
    }

    /**
     * Entfernt ausschließlich die mit dieser Onboarding-Stufe eingeführten Tabellen.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_entity_bank_accounts');
        Schema::dropIfExists('bank_directory_entries');
        Schema::dropIfExists('bank_directory_versions');
        Schema::dropIfExists('bank_directory_sources');
        Schema::dropIfExists('station_contacts');
        Schema::dropIfExists('stations');
        Schema::dropIfExists('legal_entities');
        Schema::dropIfExists('fuel_station_brands');
    }
};
