<?php

namespace App\Livewire\Storefront;

use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Run;
use App\Services\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Box catalogue and add-ons for the next Run, with add-to-cart.
 *
 * @property-read Run|null $run
 * @property-read Collection<int, BoxTemplate> $boxes
 * @property-read Collection<int, AddOn> $addOns
 */
class BoxCatalogue extends Component
{
    #[Locked]
    public ?int $runId = null;

    public function mount(?Run $run = null): void
    {
        $this->runId = $run?->id;
    }

    #[Computed]
    public function run(): ?Run
    {
        return $this->runId ? Run::query()->find($this->runId) : null;
    }

    /**
     * @return Collection<int, BoxTemplate>
     */
    #[Computed]
    public function boxes(): Collection
    {
        if ($this->run !== null) {
            return $this->run->boxTemplates()->active()->with('items.packUnit')->get();
        }

        // No Run scheduled yet: still show the range so visitors know what to expect.
        return BoxTemplate::query()->active()->with('items.packUnit')->get();
    }

    /**
     * @return Collection<int, AddOn>
     */
    #[Computed]
    public function addOns(): Collection
    {
        return $this->run?->addOns()->active()->with('item.packUnit')->get() ?? new Collection;
    }

    public function addBox(int $boxId, Cart $cart): void
    {
        $this->add('box', $boxId, $cart);
    }

    public function addAddOn(int $addOnId, Cart $cart): void
    {
        $this->add('add_on', $addOnId, $cart);
    }

    private function add(string $type, int $id, Cart $cart): void
    {
        $run = $this->run;

        if ($run === null || ! $run->isAcceptingOrders()) {
            $this->dispatch('notify', message: 'Ordering is not open right now.');

            return;
        }

        $offered = $type === 'box'
            ? $run->boxTemplates()->where('box_templates.id', $id)->where('is_active', true)->exists()
            : $run->addOns()->where('add_ons.id', $id)->where('is_active', true)->exists();

        if (! $offered) {
            return;
        }

        $cart->add($run, $type, $id);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.storefront.box-catalogue');
    }
}
