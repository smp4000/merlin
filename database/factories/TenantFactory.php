<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Erzeugt isolierte Mandanten für automatisierte Tenant- und Securitytests.
 *
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'display_name' => fake()->company(),
            'type' => TenantType::SingleOperator,
            'status' => TenantStatus::Active,
            'country_code' => 'DE',
            'default_locale' => 'de',
            'timezone' => 'Europe/Berlin',
        ];
    }
}
