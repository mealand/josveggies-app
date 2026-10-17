<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show(): View
    {
        return view('storefront.checkout');
    }

    public function return(Request $request): View
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        abort_if($reference === '' || strlen($reference) > 64, 404);

        return view('storefront.payment-return', ['reference' => $reference]);
    }
}
