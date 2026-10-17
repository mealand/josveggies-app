<?php

namespace App\Http\Controllers;

use App\Enums\OrderState;
use App\Models\DispatchZone;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\ProcurementService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'orders' => $user->orders()->with('run')->latest()->paginate(10),
            'creditBalance' => $user->creditBalanceKobo(),
        ]);
    }

    public function showOrder(Request $request, Order $order, ProcurementService $procurement): View
    {
        Gate::authorize('view', $order);
        $order->load(['run.hub', 'lines', 'substitutions.originalItem', 'substitutions.substituteItem', 'dispatchZone', 'handovers']);

        return view('account.order', [
            'order' => $order,
            'contents' => $procurement->packedContents($order),
        ]);
    }

    /**
     * Pickup QR code as SVG (contains the pickup code only; verification requires staff sign-in).
     */
    public function qr(Order $order): Response
    {
        Gate::authorize('view', $order);
        abort_unless(in_array($order->state, OrderState::paid(), true) && $order->pickup_code, 404);

        $svg = (new QRCode(new QROptions([
            'outputType' => 'svg',
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => 'M',
            'addQuietzone' => true,
        ])))->render('JV:'.$order->pickup_code);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }

    public function pay(Order $order, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('view', $order);

        return redirect()->away($payments->start($order));
    }

    public function credit(Request $request): View
    {
        $user = $request->user();

        return view('account.credit', [
            'entries' => $user->creditEntries()->with('order')->latest('created_at')->paginate(20),
            'balance' => $user->creditBalanceKobo(),
        ]);
    }

    public function profile(): View
    {
        return view('account.profile', ['zones' => DispatchZone::query()->active()->get()]);
    }
}
