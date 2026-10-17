<?php

namespace App\Services;

use App\Enums\OrderLineType;
use App\Models\Item;
use App\Models\Order;
use App\Models\Run;
use Illuminate\Support\Collection;

/**
 * Turns confirmed orders into the market-day procurement list and per-order packing lists.
 */
class ProcurementService
{
    /**
     * Total quantity of every item to buy for the Run, after substitutions and unavailable add-ons.
     *
     * @return Collection<int, array{item_id: int, name: string, pack: string|null, category: string, quantity: int, estimated_cost_kobo: int}>
     */
    public function procurementList(Run $run): Collection
    {
        $totals = [];

        foreach ($this->paidOrders($run) as $order) {
            foreach ($this->packedContents($order) as $row) {
                if ($row['item_id'] === null || $row['unavailable']) {
                    continue;
                }
                $totals[$row['item_id']] = ($totals[$row['item_id']] ?? 0) + $row['quantity'];
            }
        }

        $items = Item::query()->with('packUnit')->whereIn('id', array_keys($totals))->get()->keyBy('id');

        return collect($totals)
            ->map(function (int $quantity, int $itemId) use ($items): array {
                /** @var Item $item */
                $item = $items[$itemId];

                return [
                    'item_id' => $itemId,
                    'name' => $item->name,
                    'pack' => $item->packUnit?->traditional_name,
                    'category' => $item->category,
                    'quantity' => $quantity,
                    'estimated_cost_kobo' => $quantity * $item->reference_price_kobo,
                ];
            })
            ->sortBy([['category', 'asc'], ['name', 'asc']])
            ->values();
    }

    /**
     * What should physically go into this order (box contents with substitutions applied).
     *
     * @return list<array{line: string, item_id: int|null, name: string, quantity: int, note: string|null, unavailable: bool}>
     */
    public function packedContents(Order $order): array
    {
        $rows = [];
        $order->loadMissing(['lines.substitutions.substituteItem']);

        foreach ($order->lines as $line) {
            if ($line->type === OrderLineType::AddOn) {
                $rows[] = [
                    'line' => 'Add-on',
                    'item_id' => $line->addOn?->item_id,
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'note' => $line->unavailable_at ? 'Unavailable — credited' : null,
                    'unavailable' => $line->unavailable_at !== null,
                ];

                continue;
            }

            $substitutions = $line->substitutions->keyBy('original_item_id');

            foreach ($line->contents ?? [] as $content) {
                $quantity = (int) $content['quantity'] * $line->quantity;
                $substitution = $substitutions->get($content['item_id']);

                if ($substitution === null) {
                    $rows[] = ['line' => $line->name, 'item_id' => (int) $content['item_id'], 'name' => $content['name'], 'quantity' => $quantity, 'note' => null, 'unavailable' => false];
                } elseif ($substitution->substituteItem !== null) {
                    $rows[] = ['line' => $line->name, 'item_id' => $substitution->substituteItem->id, 'name' => $substitution->substituteItem->name, 'quantity' => $quantity, 'note' => 'Substitute for '.$content['name'], 'unavailable' => false];
                } else {
                    $rows[] = ['line' => $line->name, 'item_id' => (int) $content['item_id'], 'name' => $content['name'], 'quantity' => $quantity, 'note' => 'Unavailable — credited', 'unavailable' => true];
                }
            }
        }

        return $rows;
    }

    /**
     * @return Collection<int, Order>
     */
    public function paidOrders(Run $run): Collection
    {
        return $run->paidOrders()
            ->with(['user', 'dispatchZone', 'lines.addOn', 'lines.substitutions.substituteItem'])
            ->orderBy('fulfilment')
            ->orderBy('number')
            ->get();
    }
}
