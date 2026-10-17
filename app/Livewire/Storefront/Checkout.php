<?php

namespace App\Livewire\Storefront;

use App\Enums\Fulfilment;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\DispatchZone;
use App\Models\Policy;
use App\Models\Run;
use App\Services\Cart;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Run|null $run
 * @property-read array{lines: list<array<string, mixed>>, subtotal_kobo: int, box_count: int} $summary
 * @property-read Collection<int, DispatchZone> $zones
 * @property-read int $dispatchFee
 * @property-read int $creditBalance
 * @property-read int $creditApplied
 * @property-read int $total
 */
class Checkout extends Component
{
    public string $fulfilment = 'pickup';

    public ?int $dispatchZoneId = null;

    public string $deliveryAddress = '';

    public string $phone = '';

    public bool $useCredit = true;

    public bool $acceptForfeiture = false;

    public bool $acceptSubstitution = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->phone = (string) $user->phone;
        $this->deliveryAddress = (string) $user->default_address;
        $this->dispatchZoneId = $user->default_dispatch_zone_id;
    }

    #[Computed]
    public function run(): ?Run
    {
        $runId = app(Cart::class)->raw()['run_id'];

        return $runId ? Run::query()->find($runId) : null;
    }

    /**
     * @return array{lines: list<array<string, mixed>>, subtotal_kobo: int, box_count: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(Cart::class)->summary($this->run);
    }

    #[Computed]
    public function zones()
    {
        return DispatchZone::query()->active()->get();
    }

    #[Computed]
    public function dispatchFee(): int
    {
        if ($this->fulfilment !== Fulfilment::Dispatch->value) {
            return 0;
        }

        return (int) ($this->zones->firstWhere('id', $this->dispatchZoneId)->fee_kobo ?? 0);
    }

    #[Computed]
    public function creditBalance(): int
    {
        return Auth::user()->creditBalanceKobo();
    }

    #[Computed]
    public function creditApplied(): int
    {
        return $this->useCredit ? min($this->creditBalance, $this->summary['subtotal_kobo'] + $this->dispatchFee) : 0;
    }

    #[Computed]
    public function total(): int
    {
        return $this->summary['subtotal_kobo'] + $this->dispatchFee - $this->creditApplied;
    }

    #[Computed]
    public function policies(): array
    {
        return [
            'forfeiture' => Policy::current('forfeiture'),
            'substitution' => Policy::current('substitution'),
        ];
    }

    public function placeOrder(CheckoutService $checkout, PaymentService $payments, Cart $cart)
    {
        $this->errorMessage = null;

        $this->validate([
            'fulfilment' => ['required', 'in:pickup,dispatch'],
            'dispatchZoneId' => ['nullable', 'required_if:fulfilment,dispatch', 'integer'],
            'deliveryAddress' => ['nullable', 'required_if:fulfilment,dispatch', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'acceptForfeiture' => ['accepted'],
            'acceptSubstitution' => ['accepted'],
        ], [
            'acceptForfeiture.accepted' => 'Please accept the pickup & forfeiture policy.',
            'acceptSubstitution.accepted' => 'Please accept the substitution policy.',
            'dispatchZoneId.required_if' => 'Choose your delivery zone.',
            'deliveryAddress.required_if' => 'Enter your delivery address.',
        ]);

        $phone = Phone::normalize($this->phone);
        if ($phone === null) {
            $this->addError('phone', 'Enter a valid Nigerian mobile number.');

            return null;
        }

        $key = 'checkout:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->errorMessage = 'Too many attempts. Please wait a minute and try again.';

            return null;
        }
        RateLimiter::hit($key);

        $run = $this->run;
        if ($run === null || $run->state !== RunState::Open) {
            $this->errorMessage = 'Ordering for this Run is closed.';

            return null;
        }

        $raw = $cart->raw();
        $accepted = array_keys(array_filter([
            'forfeiture' => $this->acceptForfeiture,
            'substitution' => $this->acceptSubstitution,
        ]));

        try {
            $order = $checkout->placeOrder(
                Auth::user(),
                $run,
                array_map('intval', $raw['boxes']),
                array_map('intval', $raw['add_ons']),
                Fulfilment::from($this->fulfilment),
                $this->fulfilment === 'dispatch' ? $this->dispatchZoneId : null,
                $this->fulfilment === 'dispatch' ? $this->deliveryAddress : null,
                $phone,
                $this->useCredit,
                $accepted,
            );
        } catch (BusinessRuleException $e) {
            $this->errorMessage = $e->getMessage();

            return null;
        }

        $cart->clear();
        $this->dispatch('cart-updated');

        if ($order->total_kobo === 0) {
            return $this->redirectRoute('account.orders.show', $order);
        }

        try {
            return $this->redirect($payments->start($order));
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectRoute('account.orders.show', $order);
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout');
    }
}
