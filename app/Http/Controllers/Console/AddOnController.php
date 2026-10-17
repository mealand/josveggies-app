<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Item;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Add-ons are sold at a fixed price with a buffer built in (decided add-on pricing approach).
 */
class AddOnController extends Controller
{
    public function index(): View
    {
        return view('console.catalogue.add-ons.index', ['addOns' => AddOn::query()->with('item.packUnit')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return $this->form(new AddOn(['quantity' => 1, 'is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new AddOn);

        return redirect()->route('console.catalogue.add-ons.index')->with('success', 'Add-on created.');
    }

    public function edit(AddOn $addOn): View
    {
        return $this->form($addOn);
    }

    public function update(Request $request, AddOn $addOn): RedirectResponse
    {
        $this->save($request, $addOn);

        return redirect()->route('console.catalogue.add-ons.index')->with('success', 'Add-on updated.');
    }

    private function save(Request $request, AddOn $addOn): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'price' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $addOn->fill([
            'name' => $data['name'],
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'price_kobo' => Money::toKobo($data['price']),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        activity('catalogue')->performedOn($addOn)->causedBy($request->user())->withProperties(['price_kobo' => $addOn->price_kobo])->log('add_on.saved');
    }

    private function form(AddOn $addOn): View
    {
        return view('console.catalogue.add-ons.form', [
            'addOn' => $addOn,
            'items' => Item::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
