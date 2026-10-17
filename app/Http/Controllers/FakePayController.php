<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local-only simulated hosted checkout. Routes are registered only outside production
 * and when PAYMENT_GATEWAY=fake.
 */
class FakePayController extends Controller
{
    public function show(string $reference): View
    {
        $payment = Payment::query()->with('order')->where('reference', $reference)->firstOrFail();

        return view('dev.fake-pay', ['payment' => $payment]);
    }

    public function complete(Request $request, string $reference, PaymentGateway $gateway, PaymentService $payments): RedirectResponse
    {
        abort_unless($gateway instanceof FakeGateway, 404);

        $success = $request->input('outcome') === 'success';
        $callback = $gateway->simulate($reference, $success);
        abort_if($callback === null, 404);

        // Simulate the gateway's server-to-server webhook.
        $success ? $payments->verifyAndApply($reference) : $payments->markFailed($reference);

        return redirect()->to($callback);
    }
}
