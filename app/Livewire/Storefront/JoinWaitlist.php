<?php

namespace App\Livewire\Storefront;

use App\Models\Run;
use App\Models\WaitlistEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Full — join waitlist": the customer is notified when the next Run opens.
 * Entries against a full Run also measure unmet demand for reporting.
 */
class JoinWaitlist extends Component
{
    #[Locked]
    public ?int $runId = null;

    public bool $joined = false;

    public function mount(?Run $run = null): void
    {
        $this->runId = $run?->id;
        $this->joined = Auth::check() && WaitlistEntry::query()
            ->where('user_id', Auth::id())
            ->whereNull('notified_at')
            ->exists();
    }

    public function join()
    {
        if (! Auth::check()) {
            return $this->redirectRoute('login');
        }

        if (RateLimiter::tooManyAttempts('waitlist:'.Auth::id(), 5)) {
            return null;
        }
        RateLimiter::hit('waitlist:'.Auth::id());

        WaitlistEntry::query()->firstOrCreate(['user_id' => Auth::id(), 'run_id' => $this->runId]);
        $this->joined = true;

        return null;
    }

    public function render(): View
    {
        return view('livewire.storefront.join-waitlist');
    }
}
