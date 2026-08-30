<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt den organisatorischen Mandantenkern vor allen operativen Fachtabellen.
     *
     * Interne numerische Schlüssel dienen stabilen MySQL-Fremdschlüsseln. Öffentliche
     * Routen verwenden ausschließlich die nicht erratbare ULID in `public_id`.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('display_name', 160);
            $table->string('type', 32);
            $table->string('status', 32);
            $table->char('country_code', 2)->default('DE');
            $table->string('default_locale', 10)->default('de');
            $table->string('timezone', 64)->default('Europe/Berlin');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('tenant_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 32);
            $table->string('status', 32);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['user_id', 'status', 'valid_from', 'valid_until'], 'membership_resolution_index');
        });

        Schema::create('trials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->dateTime('started_at');
            $table->dateTime('ends_at');
            $table->unsignedTinyInteger('extension_count')->default(0);
            $table->dateTime('extended_at')->nullable();
            $table->foreignId('extended_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('extension_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Entfernt den Mandantenkern in umgekehrter Abhängigkeitsreihenfolge.
     */
    public function down(): void
    {
        Schema::dropIfExists('trials');
        Schema::dropIfExists('tenant_memberships');
        Schema::dropIfExists('tenants');
    }
};
