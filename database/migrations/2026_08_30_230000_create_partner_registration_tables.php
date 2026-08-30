<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt die datenarme Registrierung, versionierte Nachweise und das minimale Audit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('normalized_email')->nullable()->after('email');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'normalized_email' => mb_strtolower(trim((string) $user->email)),
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('normalized_email')->nullable(false)->change();
            $table->unique('normalized_email');
        });

        Schema::create('registration_intents', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('confirmed_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('tenant_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->string('email', 254)->nullable();
            $table->string('normalized_email', 254)->nullable();
            $table->char('active_email_hash', 64)->nullable()->unique();
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('partner_display_name', 160)->nullable();
            $table->string('tenant_type', 32);
            $table->char('country_code', 2);
            $table->string('locale', 10);
            $table->char('confirmation_token_hash', 64)->nullable()->unique();
            $table->dateTime('token_expires_at');
            $table->dateTime('last_confirmation_sent_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'token_expires_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('consent_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_intent_id')->constrained()->restrictOnDelete();
            $table->string('template_key', 64);
            $table->string('template_version', 32);
            $table->char('document_digest', 64);
            $table->string('purpose', 96);
            $table->string('acceptance_type', 32);
            $table->string('locale', 10);
            $table->dateTime('accepted_at');

            $table->unique(
                ['registration_intent_id', 'template_key', 'template_version'],
                'consent_template_version_unique',
            );
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('correlation_id');
            $table->string('event_type', 96);
            $table->string('subject_type', 96);
            $table->string('subject_id', 64);
            $table->string('channel', 32);
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('correlation_id');
        });
    }

    /**
     * Entfernt den Registrierungsschnitt in sicherer Abhängigkeitsreihenfolge.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('registration_intents');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['normalized_email']);
            $table->dropColumn('normalized_email');
        });
    }
};
