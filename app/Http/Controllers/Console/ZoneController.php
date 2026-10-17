<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\DispatchZone;
use App\Models\Hub;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Abuja hub and the fixed-fee dispatch zones (BR-18).
 */
class ZoneController extends Controller
{
    public function index(): View
    {
        return view('console.zones', [
            'zones' => DispatchZone::query()->orderBy('sort_order')->orderBy('fee_kobo')->get(),
            'hubs' => Hub::query()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $zone = new DispatchZone;
        $this->saveZone($request, $zone);

        return back()->with('success', "Zone {$zone->name} added.");
    }

    public function update(Request $request, DispatchZone $zone): RedirectResponse
    {
        $this->saveZone($request, $zone);

        return back()->with('success', "Zone {$zone->name} updated. New fees apply to new orders.");
    }

    public function updateHub(Request $request, Hub $hub): RedirectResponse
    {
        $hub->fill($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'directions' => ['nullable', 'string', 'max:1000'],
            'opening_note' => ['nullable', 'string', 'max:255'],
        ]))->save();

        activity('settings')->performedOn($hub)->causedBy($request->user())->log('hub.updated');

        return back()->with('success', 'Hub details saved.');
    }

    private function saveZone(Request $request, DispatchZone $zone): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'areas' => ['nullable', 'string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $zone->fill([
            'name' => $data['name'],
            'areas' => $data['areas'] ?? null,
            'fee_kobo' => Money::toKobo($data['fee']),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        activity('settings')->performedOn($zone)->causedBy($request->user())->withProperties(['fee_kobo' => $zone->fee_kobo])->log('zone.saved');
    }
}
