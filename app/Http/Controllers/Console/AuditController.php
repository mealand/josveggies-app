<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Who changed what and when: statuses, substitutions, handovers, forfeitures, credit, settings.
 */
class AuditController extends Controller
{
    public function __invoke(Request $request): View
    {
        $log = $request->string('log')->toString();

        return view('console.audit', [
            'activities' => Activity::query()
                ->with('causer')
                ->when($log !== '', fn ($q) => $q->where('log_name', $log))
                ->latest()
                ->paginate(50)
                ->withQueryString(),
            'logs' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name'),
            'current' => $log,
        ]);
    }
}
