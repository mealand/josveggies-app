<?php

namespace App\Http\Controllers\Console;

use App\Enums\OrderState;
use App\Enums\RunState;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Run;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeRuns = Run::query()
            ->whereNotIn('state', [RunState::Draft, RunState::Reconciled, RunState::Cancelled])
            ->orderBy('opens_at')
            ->get();

        return view('console.dashboard', [
            'activeRuns' => $activeRuns,
            'drafts' => Run::query()->where('state', RunState::Draft)->count(),
            'readyForPickup' => Order::query()->where('state', OrderState::ReadyForPickup)->count(),
            'outForDelivery' => Order::query()->where('state', OrderState::OutForDelivery)->count(),
            'arrivedRun' => $activeRuns->firstWhere('state', RunState::Arrived),
        ]);
    }
}
