<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ergänzt die kleinste Plattformrolle und die Herkunft manueller Einladungen.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_platform_super_admin')->default(false)->after('email_verified_at')->index();
        });

        Schema::table('registration_intents', function (Blueprint $table): void {
            $table->string('source', 32)->default('self_service')->after('status');
            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->after('confirmed_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->index(['source', 'status', 'created_at'], 'registration_source_status_index');
        });
    }

    /**
     * Entfernt ausschließlich die Plattform-Erweiterung des Registrierungsschnitts.
     */
    public function down(): void
    {
        Schema::table('registration_intents', function (Blueprint $table): void {
            $table->dropIndex('registration_source_status_index');
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropColumn('source');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_platform_super_admin']);
            $table->dropColumn('is_platform_super_admin');
        });
    }
};
