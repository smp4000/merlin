<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vervollständigt auch einen durch die MySQL-Indexnamenbegrenzung unterbrochenen
     * lokalen Erstimport, ohne bereits geladene Katalogdaten zu entfernen.
     *
     * Auf einer frischen Installation ist diese Migration idempotent: Die korrigierte
     * Hauptmigration besitzt Index und Bankkonto-Tabelle bereits.
     */
    public function up(): void
    {
        if (Schema::hasTable('bank_directory_entries')) {
            $indexNames = collect(Schema::getIndexes('bank_directory_entries'))->pluck('name');

            if (! $indexNames->contains('bank_version_record_unique')) {
                Schema::table('bank_directory_entries', function (Blueprint $table): void {
                    $table->unique(['bank_directory_version_id', 'record_number'], 'bank_version_record_unique');
                });
            }
        }

        if (! Schema::hasTable('legal_entity_bank_accounts')) {
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
    }

    /**
     * Die Reparaturmigration entfernt nichts, weil die Hauptmigration Eigentümerin der
     * Tabellen ist und deren Rollback-Reihenfolge definiert.
     */
    public function down(): void {}
};
