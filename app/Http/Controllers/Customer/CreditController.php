<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;


class CreditController extends Controller
{
    public function index()
    {
        $balance = auth()->user()->credit->credit_balance ?? 0;
        // TODO: placeholders, replace with actual data from config or database
        $paymentMethods = [
            'venmo' => '@SmartCookieTutors',
            'zelle' => 'payments@smartcookie.com'
        ];
        return view('customer.credits.index', compact('balance', 'paymentMethods'));
    }

    public function purchase(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Tutoring Credits (Top-up)'],
                    'unit_amount' => 10000, // For example, $100 (in cents)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('customer.credits.success'),
            'cancel_url' => route('customer.credits.index'),
            'customer_email' => auth()->user()->email,
        ]);

        return redirect($checkout_session->url);

        // Stripe Checkout (via Cashier)
        // Keys are from config/services.php
        /*return auth()->user()->checkout(['price_id_from_stripe' => 1], [
            'success_url' => route('customer.credits.index') . '?success=1',
            'cancel_url' => route('customer.credits.index') . '?error=1',
        ]);*/
    }

    public function success()
    {
        return redirect()->route('customer.credits.index')->with('success', 'Credits added successfully!');
    }
}
