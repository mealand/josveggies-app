<?php

namespace App\Services;

use App\Enums\CostCategory;
use App\Enums\OrderLineType;
use App\Enums\OrderState;
use App\Enums\RunState;
use App\Models\CreditLedgerEntry;
use App\Models\OrderLine;
use App\Models\Run;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-Run reporting (Webapp Concept §12). BR-21: costs are recorded for every Run.
 */
class ReportService
{
    /**
     * @return array{boxes_kobo: int, add_ons_kobo: int, dispatch_kobo: int, credit_issued_kobo: int, revenue_kobo: int, costs: array<string, int>, cost_total_kobo: int, margin_kobo: int, margin_percent: float|null, boxes_sold: int, break_even_boxes: int|null}
     */
    public function profitAndLoss(Run $run): array
    {
        $paid = $run->orders()->whereIn('state', OrderState::paid());

        $lineTotals = OrderLine::query()
            ->whereIn('order_id', (clone $paid)->select('id'))
            ->selectRaw('type, SUM(line_total_kobo) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $dispatch = (int) (clone $paid)->sum('dispatch_fee_kobo');
        $creditIssued = (int) CreditLedgerEntry::query()
            ->whereIn('order_id', (clone $paid)->select('id'))
            ->where('amount_kobo', '>', 0)
            ->sum('amount_kobo');

        $boxes = (int) ($lineTotals[OrderLineType::Box->value] ?? 0);
        $addOns = (int) ($lineTotals[OrderLineType::AddOn->value] ?? 0);
        // Credit issued for substitutions/unavailable items is revenue given back to customers.
        $revenue = $boxes + $addOns + $dispatch - $creditIssued;

        $costs = collect(CostCategory::cases())->mapWithKeys(fn (CostCategory $c): array => [$c->value => 0])->all();
        foreach ($run->costEntries()->selectRaw('category, SUM(amount_kobo) as total')->groupBy('category')->pluck('total', 'category') as $category => $total) {
            $costs[$category] = (int) $total;
        }
        $costTotal = array_sum($costs);
        $margin = $revenue - $costTotal;

        return [
            'boxes_kobo' => $boxes,
            'add_ons_kobo' => $addOns,
            'dispatch_kobo' => $dispatch,
            'credit_issued_kobo' => $creditIssued,
            'revenue_kobo' => $revenue,
            'costs' => $costs,
            'cost_total_kobo' => $costTotal,
            'margin_kobo' => $margin,
            'margin_percent' => $revenue > 0 ? round($margin / $revenue * 100, 1) : null,
            'boxes_sold' => (int) (clone $paid)->sum('slots'),
            'break_even_boxes' => $run->break_even_boxes,
        ];
    }

    /**
     * One row per Run for the overview table.
     *
     * @return list<array<string, mixed>>
     */
    public function runOverview(int $limit = 20): array
    {
        return Run::query()
            ->whereNotIn('state', [RunState::Draft, RunState::Cancelled])
            ->withCount(['waitlistEntries'])
            ->latest('opens_at')
            ->limit($limit)
            ->get()
            ->map(function (Run $run): array {
                $pnl = $this->profitAndLoss($run);
                $paid = $run->orders()->whereIn('state', OrderState::paid());
                $finished = (clone $paid)->whereIn('state', [OrderState::Collected, OrderState::Delivered, OrderState::Forfeited])->count();
                $forfeited = (clone $paid)->where('state', OrderState::Forfeited)->count();
                $lastConfirmed = (clone $paid)->max('confirmed_at');

                return [
                    'run' => $run,
                    'pnl' => $pnl,
                    'fill_percent' => $run->fillPercent(),
                    'sell_out_hours' => $run->slots_confirmed >= $run->slot_capacity && $lastConfirmed
                        ? round($run->opens_at->diffInMinutes($lastConfirmed) / 60, 1)
                        : null,
                    'waitlist' => $run->waitlist_entries_count,
                    'forfeiture_rate' => $finished > 0 ? round($forfeited / $finished * 100, 1) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Which items are swapped most often (informs box design and farmer bookings).
     *
     * @return Collection<int, \stdClass>
     */
    public function substitutionFrequency(int $limit = 10): Collection
    {
        return DB::table('substitutions')
            ->join('items', 'items.id', '=', 'substitutions.original_item_id')
            ->selectRaw('items.name, COUNT(*) as times, SUM(substitutions.credit_kobo) as credited_kobo')
            ->groupBy('items.name')
            ->orderByDesc('times')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{customers: int, returning: int, new_last_30_days: int, credit_outstanding_kobo: int, waitlist_total: int}
     */
    public function customerActivity(): array
    {
        $perCustomer = DB::table('orders')
            ->whereIn('state', array_map(fn (OrderState $s): string => $s->value, OrderState::paid()))
            ->select('user_id', DB::raw('COUNT(*) as orders'), DB::raw('MIN(confirmed_at) as first_order'))
            ->groupBy('user_id')
            ->get();

        return [
            'customers' => $perCustomer->count(),
            'returning' => $perCustomer->where('orders', '>', 1)->count(),
            'new_last_30_days' => $perCustomer->filter(fn ($row): bool => $row->first_order !== null && now()->subDays(30)->lte($row->first_order))->count(),
            'credit_outstanding_kobo' => (int) CreditLedgerEntry::query()->sum('amount_kobo'),
            'waitlist_total' => DB::table('waitlist_entries')->whereNull('notified_at')->count(),
        ];
    }
}
