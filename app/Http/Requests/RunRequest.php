<?php

namespace App\Http\Requests;

use App\Enums\RunState;
use App\Models\Run;
use App\Services\RunScheduleRules;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Run|null $run */
        $run = $this->route('run');

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('runs', 'code')->ignore($run?->id)],
            'hub_id' => ['required', 'integer', Rule::exists('hubs', 'id')],
            'slot_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'break_even_boxes' => ['nullable', 'integer', 'min:0', 'max:500'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date'],
            'market_day' => ['required', 'date'],
            'delivery_starts_at' => ['required', 'date'],
            'box_template_ids' => ['required', 'array', 'min:1'],
            'box_template_ids.*' => ['integer', Rule::exists('box_templates', 'id')],
            'add_on_ids' => ['array'],
            'add_on_ids.*' => ['integer', Rule::exists('add_ons', 'id')],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $violations = RunScheduleRules::violations(
                    CarbonImmutable::parse($this->input('opens_at')),
                    CarbonImmutable::parse($this->input('closes_at')),
                    CarbonImmutable::parse($this->input('market_day')),
                    CarbonImmutable::parse($this->input('delivery_starts_at')),
                );

                foreach ($violations as $field => $message) {
                    $validator->errors()->add($field, $message);
                }

                /** @var Run|null $run */
                $run = $this->route('run');
                if ($run !== null && (int) $this->input('slot_capacity') < $run->slots_confirmed + $run->slots_held) {
                    $validator->errors()->add('slot_capacity', 'Capacity cannot be lower than the slots already booked or held.');
                }

                if ($run !== null && ! in_array($run->state, [RunState::Draft, RunState::Scheduled, RunState::Open], true)) {
                    $validator->errors()->add('code', 'This Run can no longer be edited.');
                }
            },
        ];
    }
}
