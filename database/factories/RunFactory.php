<?php

namespace Database\Factories;

use App\Enums\RunState;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Hub;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Run>
 */
class RunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $opens = now()->subHour()->startOfMinute();

        return [
            'code' => 'RUN-'.fake()->unique()->numerify('####'),
            'hub_id' => Hub::factory(),
            'slot_capacity' => 30,
            'opens_at' => $opens,
            'closes_at' => $opens->addDays(2),
            'market_day' => $opens->addDays(5)->startOfDay(),
            'delivery_starts_at' => $opens->addDays(6)->setTime(9, 0),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Run $run): void {
            $run->state ??= RunState::Draft;
        });
    }

    public function inState(RunState $state): static
    {
        return $this->afterMaking(fn (Run $run) => $run->state = $state);
    }

    public function open(): static
    {
        return $this->inState(RunState::Open);
    }

    /**
     * Offer a box (and optionally an add-on) on this Run.
     */
    public function offering(BoxTemplate $box, ?AddOn $addOn = null): static
    {
        return $this->afterCreating(function (Run $run) use ($box, $addOn): void {
            $run->boxTemplates()->attach($box);
            if ($addOn) {
                $run->addOns()->attach($addOn);
            }
        });
    }
}
