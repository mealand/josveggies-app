<?php

namespace Database\Factories;

use App\Models\BoxTemplate;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BoxTemplate>
 */
class BoxTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true)).' Box';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'tagline' => 'A curated box',
            'price_kobo' => 1500000,
            'accent' => 'tomato',
            'is_active' => true,
        ];
    }

    /**
     * Attach the given items (or two fresh ones) with quantity 1 each.
     *
     * @param  list<Item>|null  $items
     */
    public function withItems(?array $items = null): static
    {
        return $this->afterCreating(function (BoxTemplate $box) use ($items): void {
            $items ??= [Item::factory()->create(), Item::factory()->create()];
            $box->items()->attach(collect($items)->mapWithKeys(fn (Item $i) => [$i->id => ['quantity' => 1]])->all());
        });
    }
}
