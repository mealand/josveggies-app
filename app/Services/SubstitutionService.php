<?php

namespace App\Services;

use App\Enums\CreditReason;
use App\Enums\OrderLineType;
use App\Enums\OrderState;
use App\Exceptions\BusinessRuleException;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Run;
use App\Models\Substitution;
use App\Models\User;
use App\Notifications\OrderUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Substitutions on market day.
 *
 * BR-11: a substitute must be of equal or greater value, like-for-like where possible,
 *        and at most 2 items per box may be substituted.
 * BR-12: if no suitable substitute exists, the item's value becomes customer credit.
 * BR-13: unavailable add-ons are not substituted; their value becomes credit.
 * BR-14: customers are told about substitutions before pickup (at Packed, or immediately after).
 */
class SubstitutionService
{
    /**
     * Orders in these states can still have their contents changed.
     *
     * @var list<OrderState>
     */
    private const EDITABLE_STATES = [OrderState::Confirmed, OrderState::Purchased, OrderState::Packed];

    public function __construct(private CreditService $credit) {}

    /**
     * Swap (or credit) one item in a box line. Pass $substitute = null to credit the value instead.
     */
    public function substitute(OrderLine $line, Item $original, ?Item $substitute, User $actor, ?string $batchId = null): Substitution
    {
        return DB::transaction(function () use ($line, $original, $substitute, $actor, $batchId): Substitution {
            /** @var OrderLine $line */
            $line = OrderLine::query()->with('order.user')->lockForUpdate()->findOrFail($line->id);
            $order = $line->order;

            if ($line->type !== OrderLineType::Box) {
                throw new BusinessRuleException('Add-ons are not substituted; mark them unavailable instead.', 'BR-13');
            }

            if (! in_array($order->state, self::EDITABLE_STATES, true)) {
                throw new BusinessRuleException("Order {$order->number} can no longer be changed.");
            }

            $content = collect($line->contents ?? [])->firstWhere('item_id', $original->id);
            if ($content === null) {
                throw new BusinessRuleException("{$original->name} is not in this box.");
            }

            $alreadySubstituted = $line->substitutions()->pluck('original_item_id');
            if ($alreadySubstituted->contains($original->id)) {
                throw new BusinessRuleException("{$original->name} has already been substituted in order {$order->number}.");
            }

            $max = (int) config('josveggies.substitutions.max_items_per_box');
            if ($alreadySubstituted->count() >= $max) {
                throw new BusinessRuleException("At most {$max} items per box can be substituted.", 'BR-11');
            }

            // The substitution applies to every box on the line.
            $quantity = (int) $content['quantity'] * $line->quantity;
            $originalValue = (int) $content['unit_value_kobo'] * $quantity;

            $substitution = new Substitution;
            $substitution->order_id = $order->id;
            $substitution->order_line_id = $line->id;
            $substitution->original_item_id = $original->id;
            $substitution->quantity = $quantity;
            $substitution->original_value_kobo = $originalValue;
            $substitution->batch_id = $batchId;
            $substitution->recorded_by = $actor->id;

            if ($substitute !== null) {
                if ($substitute->id === $original->id) {
                    throw new BusinessRuleException('Choose a different item as the substitute.');
                }

                $substituteValue = $substitute->reference_price_kobo * $quantity;
                if ($substituteValue < $originalValue) {
                    throw new BusinessRuleException("{$substitute->name} is worth less than {$original->name}. Substitutes must be of equal or greater value.", 'BR-11');
                }

                $substitution->substitute_item_id = $substitute->id;
                $substitution->substitute_value_kobo = $substituteValue;
            } else {
                $substitution->credit_kobo = $originalValue;
                $this->credit->grant($order->user, $originalValue, CreditReason::Substitution, $order, "{$original->name} unavailable", $actor);
            }

            $substitution->save();
            $this->notifyIfAlreadyPacked($order->state, $order);

            activity('substitutions')
                ->performedOn($substitution)
                ->causedBy($actor)
                ->withProperties(['order' => $order->number, 'original' => $original->name, 'substitute' => $substitute?->name, 'credit_kobo' => $substitution->credit_kobo])
                ->log('substitution.recorded');

            return $substitution;
        });
    }

    /**
     * Apply the same substitution to every paid box of one type in a Run.
     *
     * @return array{applied: int, skipped: list<string>}
     */
    public function substituteAcrossRun(Run $run, int $boxTemplateId, Item $original, ?Item $substitute, User $actor): array
    {
        $batchId = (string) Str::uuid();
        $applied = 0;
        $skipped = [];

        $lines = OrderLine::query()
            ->where('type', OrderLineType::Box)
            ->where('box_template_id', $boxTemplateId)
            ->whereHas('order', fn ($q) => $q->where('run_id', $run->id)->whereIn('state', self::EDITABLE_STATES))
            ->with('order')
            ->get();

        foreach ($lines as $line) {
            try {
                $this->substitute($line, $original, $substitute, $actor, $batchId);
                $applied++;
            } catch (BusinessRuleException $e) {
                $skipped[] = $line->order->number.': '.$e->getMessage();
            }
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    /**
     * BR-13: an unavailable add-on is credited in full.
     */
    public function markAddOnUnavailable(OrderLine $line, User $actor): void
    {
        DB::transaction(function () use ($line, $actor): void {
            /** @var OrderLine $line */
            $line = OrderLine::query()->with('order.user')->lockForUpdate()->findOrFail($line->id);
            $order = $line->order;

            if ($line->type !== OrderLineType::AddOn || $line->unavailable_at !== null) {
                throw new BusinessRuleException('This add-on is already marked unavailable.');
            }

            if (! in_array($order->state, self::EDITABLE_STATES, true)) {
                throw new BusinessRuleException("Order {$order->number} can no longer be changed.");
            }

            $line->unavailable_at = now();
            $line->save();
            $this->credit->grant($order->user, $line->line_total_kobo, CreditReason::UnavailableAddOn, $order, "{$line->name} unavailable", $actor);
            $this->notifyIfAlreadyPacked($order->state, $order);

            activity('substitutions')->performedOn($line)->causedBy($actor)
                ->withProperties(['order' => $order->number, 'add_on' => $line->name, 'credit_kobo' => $line->line_total_kobo])
                ->log('add_on.unavailable');
        });
    }

    /**
     * @return array{applied: int, skipped: list<string>}
     */
    public function markAddOnUnavailableAcrossRun(Run $run, int $addOnId, User $actor): array
    {
        $applied = 0;
        $skipped = [];

        $lines = OrderLine::query()
            ->where('type', OrderLineType::AddOn)
            ->where('add_on_id', $addOnId)
            ->whereNull('unavailable_at')
            ->whereHas('order', fn ($q) => $q->where('run_id', $run->id)->whereIn('state', self::EDITABLE_STATES))
            ->with('order')
            ->get();

        foreach ($lines as $line) {
            try {
                $this->markAddOnUnavailable($line, $actor);
                $applied++;
            } catch (BusinessRuleException $e) {
                $skipped[] = $line->order->number.': '.$e->getMessage();
            }
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    /**
     * Orders that are not yet packed hear about substitutions in the "packed" update (BR-14).
     */
    private function notifyIfAlreadyPacked(OrderState $state, Order $order): void
    {
        if ($state === OrderState::Packed) {
            $order->user->notify((new OrderUpdate($order, 'substitution'))->afterCommit());
        }
    }
}
