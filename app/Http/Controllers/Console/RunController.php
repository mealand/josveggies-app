<?php

namespace App\Http\Controllers\Console;

use App\Enums\OrderState;
use App\Enums\RunState;
use App\Http\Controllers\Controller;
use App\Http\Requests\RunRequest;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Hub;
use App\Models\Run;
use App\Models\Setting;
use App\Services\RunService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RunController extends Controller
{
    public function index(): View
    {
        return view('console.runs.index', [
            'runs' => Run::query()->with('hub')->withCount('paidOrders')->latest('opens_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $next = now()->addDay()->setTime(8, 0);

        $run = new Run([
            'code' => 'RUN-'.now()->format('Y').'-'.str_pad((string) (Run::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'slot_capacity' => (int) Setting::get('default_slot_capacity'),
            'opens_at' => $next,
            'closes_at' => $next->addDays(2)->setTime(20, 0),
            'market_day' => $next->addDays(5),
            'delivery_starts_at' => $next->addDays(5)->setTime(18, 0),
        ]);

        return $this->form($run);
    }

    public function store(RunRequest $request): RedirectResponse
    {
        $run = new Run($request->safe()->except(['box_template_ids', 'add_on_ids']));
        $run->state = RunState::Draft;
        $run->save();
        $run->boxTemplates()->sync($request->validated('box_template_ids'));
        $run->addOns()->sync($request->validated('add_on_ids', []));

        activity('runs')->performedOn($run)->causedBy($request->user())->log("Run {$run->code} created");

        return redirect()->route('console.runs.show', $run)->with('success', 'Run created as a draft. Publish it when ready.');
    }

    public function edit(Run $run): View
    {
        return $this->form($run);
    }

    public function update(RunRequest $request, Run $run): RedirectResponse
    {
        $run->fill($request->safe()->except(['box_template_ids', 'add_on_ids']))->save();
        $run->boxTemplates()->sync($request->validated('box_template_ids'));
        $run->addOns()->sync($request->validated('add_on_ids', []));

        activity('runs')->performedOn($run)->causedBy($request->user())->withProperties($run->getChanges())->log("Run {$run->code} updated");

        return redirect()->route('console.runs.show', $run)->with('success', 'Run updated.');
    }

    public function show(Request $request, Run $run): View
    {
        $run->load(['hub', 'boxTemplates', 'addOns']);

        $orders = $run->orders()
            ->whereNotIn('state', [OrderState::Expired])
            ->with(['user', 'dispatchZone'])
            ->orderBy('number')
            ->get();

        $user = $request->user();
        $actions = collect($run->state->allowedTransitions())
            ->filter(fn (RunState $to): bool => $user->hasRole(...$to->rolesAllowedToEnter()))
            // Opening, closing and forfeiture normally happen on schedule; keep them available to Admin only.
            ->values();

        return view('console.runs.show', [
            'run' => $run,
            'orders' => $orders,
            'actions' => $actions,
            'stateCounts' => $orders->countBy(fn ($o) => $o->state->value),
        ]);
    }

    public function transition(Request $request, Run $run, RunService $runs): RedirectResponse
    {
        $validated = $request->validate(['to' => ['required', Rule::enum(RunState::class)]]);
        $to = RunState::from($validated['to']);

        $runs->transition($run, $to, $request->user());

        return back()->with('success', "Run {$run->code} is now {$to->label()}.");
    }

    public function purchased(Request $request, Run $run, RunService $runs): RedirectResponse
    {
        $runs->markPurchased($run, $request->user());

        return back()->with('success', 'Marked as purchased. Customers have been notified.');
    }

    private function form(Run $run): View
    {
        return view('console.runs.form', [
            'run' => $run,
            'hubs' => Hub::query()->where('is_active', true)->get(),
            'boxes' => BoxTemplate::query()->active()->get(),
            'addOns' => AddOn::query()->active()->get(),
            'selectedBoxes' => $run->exists ? $run->boxTemplates()->pluck('box_templates.id')->all() : BoxTemplate::query()->active()->pluck('id')->all(),
            'selectedAddOns' => $run->exists ? $run->addOns()->pluck('add_ons.id')->all() : AddOn::query()->active()->pluck('id')->all(),
        ]);
    }
}
