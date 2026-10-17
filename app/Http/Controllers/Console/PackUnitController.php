<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\PackUnit;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Standardised market units: traditional name tied to a container and weight range.
 */
class PackUnitController extends Controller
{
    public function index(): View
    {
        return view('console.catalogue.pack-units.index', ['packUnits' => PackUnit::query()->withCount('items')->orderBy('traditional_name')->get()]);
    }

    public function create(): View
    {
        return view('console.catalogue.pack-units.form', ['packUnit' => new PackUnit]);
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $this->save($request, new PackUnit, $images);

        return redirect()->route('console.catalogue.pack-units.index')->with('success', 'Pack unit added.');
    }

    public function edit(PackUnit $packUnit): View
    {
        return view('console.catalogue.pack-units.form', ['packUnit' => $packUnit]);
    }

    public function update(Request $request, PackUnit $packUnit, ImageService $images): RedirectResponse
    {
        $this->save($request, $packUnit, $images);

        return redirect()->route('console.catalogue.pack-units.index')->with('success', 'Pack unit updated.');
    }

    private function save(Request $request, PackUnit $packUnit, ImageService $images): void
    {
        $data = $request->validate([
            'traditional_name' => ['required', 'string', 'max:60'],
            'container' => ['required', 'string', 'max:80'],
            'min_weight_g' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'max_weight_g' => ['nullable', 'integer', 'gte:min_weight_g', 'max:100000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $packUnit->fill(collect($data)->except('photo')->all());

        if ($request->hasFile('photo')) {
            $packUnit->photo_path = $images->storeProductPhoto($request->file('photo'), $packUnit->photo_path);
        }

        $packUnit->save();
        activity('catalogue')->performedOn($packUnit)->causedBy($request->user())->log('pack_unit.saved');
    }
}
