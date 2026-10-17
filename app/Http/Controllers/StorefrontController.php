<?php

namespace App\Http\Controllers;

use App\Enums\RunState;
use App\Models\DispatchZone;
use App\Models\Hub;
use App\Models\Policy;
use App\Models\Run;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $runs = Run::query()->publiclyVisible()->get();

        // Prefer the Run taking orders, then the next scheduled one, then the latest closed one.
        $run = $runs->firstWhere('state', RunState::Open)
            ?? $runs->firstWhere('state', RunState::Scheduled)
            ?? $runs->firstWhere('state', RunState::Closed);

        $nextRun = $runs->first(fn (Run $r): bool => $r->state === RunState::Scheduled && $r->id !== $run?->id);

        return view('storefront.home', [
            'run' => $run,
            'nextRun' => $nextRun,
            'zones' => DispatchZone::query()->active()->get(),
            'hub' => $run->hub ?? Hub::query()->where('is_active', true)->first(),
            'produce' => SiteContent::produce(),
            'steps' => SiteContent::steps(),
            'faqs' => SiteContent::faqs(),
        ]);
    }

    public function how(): View
    {
        return view('storefront.how', [
            'steps' => SiteContent::steps(),
            'zones' => DispatchZone::query()->active()->get(),
            'hub' => Hub::query()->where('is_active', true)->first(),
        ]);
    }

    public function faq(): View
    {
        return view('storefront.faq', ['faqs' => SiteContent::faqs()]);
    }

    public function contact(): View
    {
        return view('storefront.contact', ['hub' => Hub::query()->where('is_active', true)->first()]);
    }

    public function policy(string $slug): View
    {
        abort_unless(array_key_exists($slug, Policy::SLUGS), 404);

        $policy = Policy::current($slug);

        return view('storefront.policy', [
            'title' => Policy::SLUGS[$slug],
            'policy' => $policy,
            // Markdown authored by Admin; raw HTML is escaped and unsafe links stripped.
            'html' => $policy ? Str::markdown($policy->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) : null,
        ]);
    }
}
