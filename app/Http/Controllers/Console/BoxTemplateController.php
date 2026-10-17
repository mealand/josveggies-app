<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\BoxTemplate;
use App\Models\Item;
use App\Services\ImageService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BoxTemplateController extends Controller
{
    public const ACCENTS = ['tomato' => 'Tomato red', 'leaf' => 'Leaf green', 'salad' => 'Pepper yellow'];

    public function index(): View
    {
        return view('console.catalogue.boxes.index', [
            'boxes' => BoxTemplate::query()->withCount('items')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new BoxTemplate(['accent' => 'tomato', 'is_active' => true]));
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $box = new BoxTemplate;
        $this->save($request, $box, $images);

        return redirect()->route('console.catalogue.boxes.index')->with('success', "{$box->name} created.");
    }

    public function edit(BoxTemplate $box): View
    {
        return $this->form($box->load('items'));
    }

    public function update(Request $request, BoxTemplate $box, ImageService $images): RedirectResponse
    {
        $this->save($request, $box, $images);

        return redirect()->route('console.catalogue.boxes.index')->with('success', "{$box->name} updated. Existing orders keep their original contents.");
    }

    private function save(Request $request, BoxTemplate $box, ImageService $images): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'accent' => ['required', Rule::in(array_keys(self::ACCENTS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'contents' => ['required', 'array', 'min:1'],
            'contents.*' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $contents = collect($data['contents'])->filter(fn ($qty) => (int) $qty > 0)->map(fn ($qty) => ['quantity' => (int) $qty]);
        abort_if($contents->isEmpty(), 422, 'A box needs at least one item.');
        abort_unless(Item::query()->whereIn('id', $contents->keys())->count() === $contents->count(), 422);

        $box->fill([
            'name' => $data['name'],
            'slug' => $box->slug ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'price_kobo' => Money::toKobo($data['price']),
            'accent' => $data['accent'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->hasFile('photo')) {
            $box->photo_path = $images->storeProductPhoto($request->file('photo'), $box->photo_path);
        }

        $box->save();
        $box->items()->sync($contents->all());

        activity('catalogue')->performedOn($box)->causedBy($request->user())->withProperties(['price_kobo' => $box->price_kobo, 'contents' => $contents])->log('box.saved');
    }

    private function form(BoxTemplate $box): View
    {
        return view('console.catalogue.boxes.form', [
            'box' => $box,
            'items' => Item::query()->with('packUnit')->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'quantities' => $box->exists ? $box->items->pluck('pivot.quantity', 'id')->all() : [],
            'accents' => self::ACCENTS,
        ]);
    }
}
