<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erlaubt unvollständige Entwürfe in den bisherigen Bootstrap-Pflichtspalten.
     *
     * Die Vollständigkeit einer aktiven Gesellschaft wird im Anwendungsdienst geprüft.
     * Die Datenbank darf Entwürfe hingegen ohne erfundene Platzhalterwerte speichern.
     */
    public function up(): void
    {
        Schema::table('legal_entities', function (Blueprint $table): void {
            $table->string('legal_name', 200)->nullable()->change();
            $table->string('legal_form', 80)->nullable()->change();
            $table->string('street', 160)->nullable()->change();
            $table->string('house_number', 30)->nullable()->change();
            $table->string('postal_code', 20)->nullable()->change();
            $table->string('city', 120)->nullable()->change();
            $table->string('region', 120)->nullable()->change();
            $table->char('country_code', 2)->nullable()->default(null)->change();
            $table->string('billing_email', 254)->nullable()->change();
        });
    }

    /**
     * Verweigert einen verlustbehafteten Rollback, solange echte unvollständige Entwürfe
     * existieren. Vollständige Bestandsdaten können weiterhin sauber zurückgeführt werden.
     */
    public function down(): void
    {
        $nullableColumns = [
            'legal_name',
            'legal_form',
            'street',
            'house_number',
            'postal_code',
            'city',
            'region',
            'country_code',
            'billing_email',
        ];

        $hasIncompleteDraft = DB::table('legal_entities')
            ->where(function ($query) use ($nullableColumns): void {
                foreach ($nullableColumns as $column) {
                    $query->orWhereNull($column);
                }
            })
            ->exists();

        if ($hasIncompleteDraft) {
            throw new RuntimeException('Rollback abgebrochen: Unvollständige Gesellschaftsentwürfe müssen zuerst vervollständigt werden.');
        }

        Schema::table('legal_entities', function (Blueprint $table): void {
            $table->string('legal_name', 200)->nullable(false)->change();
            $table->string('legal_form', 80)->nullable(false)->change();
            $table->string('street', 160)->nullable(false)->change();
            $table->string('house_number', 30)->nullable(false)->change();
            $table->string('postal_code', 20)->nullable(false)->change();
            $table->string('city', 120)->nullable(false)->change();
            $table->string('region', 120)->nullable(false)->change();
            $table->char('country_code', 2)->nullable(false)->default('DE')->change();
            $table->string('billing_email', 254)->nullable(false)->change();
        });
    }
};
