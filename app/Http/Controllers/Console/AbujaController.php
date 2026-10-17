<?php

namespace App\Http\Controllers\Console;

use App\Enums\OrderState;
use App\Http\Controllers\Controller;
use App\Models\Run;
use Illuminate\Contracts\View\View;

class AbujaController extends Controller
{
    public function pickup(): View
    {
        return view('console.abuja.pickup');
    }

    public function dispatch(): View
    {
        return view('console.abuja.dispatch');
    }

    public function forfeitures(Run $run): View
    {
        return view('console.abuja.forfeitures', [
            'run' => $run,
            'orders' => $run->orders()->where('state', OrderState::Forfeited)->with(['user', 'dispatchZone'])->orderBy('number')->get(),
        ]);
    }
}
