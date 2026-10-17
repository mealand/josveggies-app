<?php

namespace App\Http\Controllers\Console;

use App\Enums\CostCategory;
use App\Enums\RunState;
use App\Http\Controllers\Controller;
use App\Models\Run;
use App\Models\RunCostEntry;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        return view('console.reports.index', [
            'overview' => $this->reports->runOverview(),
            'substitutions' => $this->reports->substitutionFrequency(),
            'customers' => $this->reports->customerActivity(),
        ]);
    }

    public function run(Run $run): View
    {
        return view('console.reports.run', [
            'run' => $run,
            'pnl' => $this->reports->profitAndLoss($run),
            'costs' => $run->costEntries()->with('recorder')->latest()->get(),
            'categories' => CostCategory::cases(),
        ]);
    }

    /**
     * BR-21: every Run records its costs, even below break-even.
     */
    public function storeCost(Request $request, Run $run): RedirectResponse
    {
        abort_if(in_array($run->state, [RunState::Reconciled, RunState::Draft], true), 422, 'Costs cannot be added to this Run.');

        $data = $request->validate([
            'category' => ['required', Rule::enum(CostCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $entry = new RunCostEntry([
            'category' => $data['category'],
            'amount_kobo' => Money::toKobo($data['amount']),
            'description' => $data['description'],
        ]);
        $entry->run_id = $run->id;
        $entry->recorded_by = $request->user()->id;
        $entry->save();

        activity('costs')->performedOn($entry)->causedBy($request->user())->withProperties($entry->only(['category', 'amount_kobo']))->log('cost.recorded');

        return back()->with('success', 'Cost recorded.');
    }
}
