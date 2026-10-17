<?php

namespace App\Livewire\Console;

use App\Enums\OrderLineType;
use App\Enums\OrderState;
use App\Exceptions\BusinessRuleException;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Item;
use App\Models\OrderLine;
use App\Models\Run;
use App\Models\Substitution;
use App\Services\SubstitutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Record substitutions per order or across all boxes of a type (BR-11 to BR-13).
 *
 * @property-read Run $run
 * @property-read Collection<int, BoxTemplate> $boxTemplates
 * @property-read Collection<int, OrderLine> $boxLines
 * @property-read Collection<int, Item> $originalItems
 * @property-read Collection<int, Item> $substitutes
 * @property-read Collection<int, Substitution> $recorded
 * @property-read Collection<int, AddOn> $addOns
 */
class SubstitutionManager extends Component
{
    #[Locked]
    public int $runId;

    public string $scope = 'box_type';

    public ?int $boxTemplateId = null;

    public ?int $orderLineId = null;

    public ?int $originalItemId = null;

    public ?int $substituteItemId = null;

    public ?int $addOnId = null;

    public ?string $result = null;

    /** @var list<string> */
    public array $skipped = [];

    public function mount(Run $run): void
    {
        Gate::authorize('jos-ops');
        $this->runId = $run->id;
    }

    #[Computed]
    public function run(): Run
    {
        return Run::query()->findOrFail($this->runId);
    }

    #[Computed]
    public function boxTemplates()
    {
        return $this->run->boxTemplates()->with('items')->get();
    }

    #[Computed]
    public function boxLines()
    {
        return OrderLine::query()
            ->where('type', OrderLineType::Box)
            ->whereHas('order', fn ($q) => $q->where('run_id', $this->runId)->whereIn('state', OrderState::paid()))
            ->with('order.user')
            ->get();
    }

    /**
     * Items in the selected box template or order line.
     */
    #[Computed]
    public function originalItems()
    {
        if ($this->scope === 'order' && $this->orderLineId) {
            $line = $this->boxLines->firstWhere('id', $this->orderLineId);

            return Item::query()->whereIn('id', collect($line->contents ?? [])->pluck('item_id'))->get();
        }

        return $this->boxTemplates->firstWhere('id', $this->boxTemplateId)->items ?? collect();
    }

    /**
     * Candidate substitutes: same category first (like-for-like), never cheaper.
     */
    #[Computed]
    public function substitutes()
    {
        $original = Item::query()->find($this->originalItemId);

        if ($original === null) {
            return collect();
        }

        return Item::query()
            ->where('is_active', true)
            ->whereKeyNot($original->id)
            ->where('reference_price_kobo', '>=', $original->reference_price_kobo)
            ->get()
            ->sortBy(fn (Item $item) => [$item->category === $original->category ? 0 : 1, $item->reference_price_kobo]);
    }

    #[Computed]
    public function recorded()
    {
        return Substitution::query()
            ->whereHas('order', fn ($q) => $q->where('run_id', $this->runId))
            ->with(['order', 'originalItem', 'substituteItem'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function addOns()
    {
        return $this->run->addOns;
    }

    public function updatedScope(): void
    {
        $this->reset(['orderLineId', 'boxTemplateId', 'originalItemId', 'substituteItemId']);
    }

    public function updatedOriginalItemId(): void
    {
        $this->substituteItemId = null;
    }

    public function apply(SubstitutionService $service): void
    {
        Gate::authorize('jos-ops');
        $this->validate([
            'originalItemId' => ['required', 'integer'],
            'substituteItemId' => ['nullable', 'integer'],
            'boxTemplateId' => ['required_if:scope,box_type', 'nullable', 'integer'],
            'orderLineId' => ['required_if:scope,order', 'nullable', 'integer'],
        ]);

        $original = Item::query()->findOrFail($this->originalItemId);
        $substitute = $this->substituteItemId ? Item::query()->findOrFail($this->substituteItemId) : null;
        $this->skipped = [];

        try {
            if ($this->scope === 'order') {
                $line = $this->boxLines->firstWhere('id', $this->orderLineId) ?? throw new BusinessRuleException('Choose an order.');
                $service->substitute($line, $original, $substitute, auth()->user());
                $this->result = 'Substitution recorded for '.$line->order->number.'.';
            } else {
                $template = BoxTemplate::query()->findOrFail($this->boxTemplateId);
                $outcome = $service->substituteAcrossRun($this->run, $template->id, $original, $substitute, auth()->user());
                $this->result = "Applied to {$outcome['applied']} {$template->name} line(s).";
                $this->skipped = $outcome['skipped'];
            }
        } catch (BusinessRuleException $e) {
            $this->result = null;
            $this->addError('substituteItemId', $e->getMessage());

            return;
        }

        $this->reset(['originalItemId', 'substituteItemId']);
        unset($this->recorded);
    }

    public function markAddOnUnavailable(SubstitutionService $service): void
    {
        Gate::authorize('jos-ops');
        $this->validate(['addOnId' => ['required', 'integer']]);
        $addOn = AddOn::query()->findOrFail($this->addOnId);

        $outcome = $service->markAddOnUnavailableAcrossRun($this->run, $addOn->id, auth()->user());
        $this->result = "{$addOn->name}: credited on {$outcome['applied']} order line(s).";
        $this->skipped = $outcome['skipped'];
        $this->addOnId = null;
    }

    public function render(): View
    {
        return view('livewire.console.substitution-manager');
    }
}
