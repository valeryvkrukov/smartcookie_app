<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\Checkout\Session;


class CreditController extends Controller
{
    public function index()
    {
        $balance = auth()->user()->credit->credit_balance ?? 0;

        $paymentMethods = [
            'venmo' => [
                'username' => config('payments.venmo.username'),
                'note' => config('payments.venmo.note'),
                'web_url' => 'https://venmo.com/'.ltrim(config('payments.venmo.username'), '@'),
                'deep_link' => 'venmo://paycharge?txn=pay&recipients='.urlencode(config('payments.venmo.username')).'&note='.urlencode(config('payments.venmo.note')),
            ],
            'zelle' => [
                'email' => config('payments.zelle.email'),
                'note' => config('payments.zelle.note'),
            ],
        ];

        return view('customer.credits.index', compact('balance', 'paymentMethods'));
    }

    public function purchase(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['stripe', 'venmo', 'zelle'])],
        ]);

        if ($data['payment_method'] === 'stripe') {
            Stripe::setApiKey(config('services.stripe.secret'));

            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('payments.stripe.currency', 'usd'),
                        'product_data' => ['name' => config('payments.stripe.description', 'Tutoring Credits (Top-up)')],
                        'unit_amount' => config('payments.stripe.amount', 10000),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('customer.credits.success'),
                'cancel_url' => route('customer.credits.index'),
                'customer_email' => auth()->user()->email,
            ]);

            return redirect($checkout_session->url);
        }

        if ($data['payment_method'] === 'venmo') {
            $username = config('payments.venmo.username');
            $url = 'https://venmo.com/'.ltrim($username, '@');
            return redirect()->away($url);
        }

        return redirect()->route('customer.credits.index')
            ->with('payment_instructions', [
                'method' => 'Zelle',
                'email' => config('payments.zelle.email'),
                'note' => config('payments.zelle.note'),
            ]);
    }

    public function success()
    {
        return redirect()->route('customer.credits.index')->with('success', 'Credits added successfully!');
    }
}
