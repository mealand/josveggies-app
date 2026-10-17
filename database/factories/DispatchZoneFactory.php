<?php

namespace Database\Factories;

use App\Models\DispatchZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DispatchZone>
 */
class DispatchZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Central', 'Inner', 'Outer']).' Abuja',
            'areas' => 'Wuse, Maitama, Garki',
            'fee_kobo' => 150000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
