<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddOn>
 */
class AddOnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'name' => 'Extra '.fake()->word(),
            'quantity' => 1,
            'price_kobo' => 250000,
            'is_active' => true,
        ];
    }
}
