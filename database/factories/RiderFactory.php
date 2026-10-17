<?php

namespace Database\Factories;

use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rider>
 */
class RiderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName().' (Rider)',
            'phone' => '23490'.fake()->numerify('########'),
            'company' => 'Swift Riders',
            'is_active' => true,
        ];
    }
}
