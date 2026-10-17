<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin-editable settings and policy text (Maintainability NFR: no code changes needed).
 */
class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('console.settings', [
            'settings' => collect(array_keys(Setting::DEFAULTS))->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]),
            'policies' => collect(Policy::SLUGS)->map(fn (string $title, string $slug) => Policy::current($slug)),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $time = ['required', 'date_format:H:i'];
        $data = $request->validate([
            'hold_minutes' => ['required', 'integer', 'min:5', 'max:30'],
            'morning_reminder_time' => $time,
            'final_reminder_time' => $time,
            'pickup_deadline_time' => $time,
            'default_slot_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'announcement' => ['nullable', 'string', 'max:200'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, in_array($key, ['hold_minutes', 'default_slot_capacity'], true) ? (int) $value : (string) $value);
        }

        activity('settings')->causedBy($request->user())->withProperties($data)->log('settings.updated');

        return back()->with('success', 'Settings saved.');
    }

    /**
     * Each edit creates a new policy version; orders record which versions were accepted.
     */
    public function updatePolicy(Request $request, string $slug): RedirectResponse
    {
        abort_unless(array_key_exists($slug, Policy::SLUGS), 404);

        $data = $request->validate(['body' => ['required', 'string', 'max:50000']]);
        $current = Policy::current($slug);

        if ($current?->body === $data['body']) {
            return back()->with('success', 'No changes.');
        }

        $policy = Policy::query()->create([
            'slug' => $slug,
            'title' => Policy::SLUGS[$slug],
            'body' => $data['body'],
            'version' => ($current->version ?? 0) + 1,
            'updated_by' => $request->user()->id,
        ]);

        activity('settings')->performedOn($policy)->causedBy($request->user())->log("policy.{$slug}.v{$policy->version}");

        return back()->with('success', Policy::SLUGS[$slug]." published as version {$policy->version}.");
    }
}
