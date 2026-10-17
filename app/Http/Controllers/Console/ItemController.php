<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PackUnit;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Items and their reference prices. Reference prices drive substitution value checks (BR-11)
 * and credit amounts, so Admin updates them after each round of farmer pre-booking.
 */
class ItemController extends Controller
{
    public function index(): View
    {
        return view('console.catalogue.items.index', [
            'items' => Item::query()->with('packUnit')->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Item(['is_active' => true, 'category' => 'other']));
    }

    public function store(Request $request): RedirectResponse
    {
        $item = new Item;
        $this->save($request, $item);

        return redirect()->route('console.catalogue.items.index')->with('success', "{$item->name} added.");
    }

    public function edit(Item $item): View
    {
        return $this->form($item);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $this->save($request, $item);

        return redirect()->route('console.catalogue.items.index')->with('success', "{$item->name} updated.");
    }

    private function save(Request $request, Item $item): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'category' => ['required', Rule::in(array_keys(Item::CATEGORIES))],
            'pack_unit_id' => ['nullable', 'integer', Rule::exists('pack_units', 'id')],
            'reference_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'is_active' => ['boolean'],
        ]);

        $before = $item->reference_price_kobo;
        $item->fill([
            'name' => $data['name'],
            'category' => $data['category'],
            'pack_unit_id' => $data['pack_unit_id'] ?? null,
            'reference_price_kobo' => Money::toKobo($data['reference_price']),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        activity('catalogue')->performedOn($item)->causedBy($request->user())
            ->withProperties(['reference_price_kobo' => ['from' => $before, 'to' => $item->reference_price_kobo]])
            ->log('item.saved');
    }

    private function form(Item $item): View
    {
        return view('console.catalogue.items.form', [
            'item' => $item,
            'packUnits' => PackUnit::query()->orderBy('traditional_name')->get(),
        ]);
    }
}
