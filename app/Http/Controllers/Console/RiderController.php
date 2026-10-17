<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Third-party riders stationed at the hub. In v1 they don't log in; Abuja Ops records handovers.
 */
class RiderController extends Controller
{
    public function index(): View
    {
        return view('console.riders', ['riders' => Rider::query()->withCount('handovers')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rider = new Rider;
        $this->save($request, $rider);

        return back()->with('success', "{$rider->name} added.");
    }

    public function update(Request $request, Rider $rider): RedirectResponse
    {
        $this->save($request, $rider);

        return back()->with('success', "{$rider->name} updated.");
    }

    private function save(Request $request, Rider $rider): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
        ]);

        $rider->fill([
            'name' => $data['name'],
            'phone' => Phone::normalize($data['phone']) ?? $data['phone'],
            'company' => $data['company'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        activity('riders')->performedOn($rider)->causedBy($request->user())->log('rider.saved');
    }
}
