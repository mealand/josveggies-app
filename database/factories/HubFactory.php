<?php

namespace Database\Factories;

use App\Models\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hub>
 */
class HubFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'JosVeggies Hub, Wuse 2',
            'address' => fake()->streetAddress().', Wuse 2, Abuja',
            'directions' => 'Opposite the plaza, look for the green JosVeggies banner.',
            'opening_note' => 'Lit and staffed through the night during pickup windows.',
            'is_active' => true,
        ];
    }
}
