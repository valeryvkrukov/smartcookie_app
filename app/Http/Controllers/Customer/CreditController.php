<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CreditPurchase;
use App\Models\User;
use App\Notifications\CreditBalanceChanged;
use App\Notifications\FirstCreditPurchase;
use App\Notifications\LowCreditBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\Checkout\Session;


class CreditController extends Controller
{
    /** Credit packs available for repeat buyers */
    private const REPEAT_PACKS = [4, 6, 8, 10];

    public function index()
    {
        $user        = auth()->user();
        $credit      = $user->credit;
        $balance     = $credit->credit_balance ?? 0;
        $ratePerCredit = $credit->dollar_cost_per_credit ?? null;
        $isFirstPurchase = !CreditPurchase::where('user_id', $user->id)->exists();

        $venmoUser  = ltrim(config('payments.venmo.username'), '@');
        $venmoNote  = config('payments.venmo.note');
        $zellePhone = config('payments.zelle.phone');

        $paymentMethods = [
            'venmo' => [
                'username' => '@' . $venmoUser,
                'note'     => $venmoNote,
                'web_url'  => 'https://venmo.com/' . $venmoUser,
            ],
            'zelle' => [
                'phone'  => $zellePhone,
                'note'   => config('payments.zelle.note'),
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&ecc=L&charset-source=UTF-8&data=' . urlencode($zellePhone),
            ],
        ];

        // Pre-build Venmo QR URLs server-side for every available pack
        // so the view never needs to construct URLs in JavaScript.
        $venmoQrUrls = [];
        if ($ratePerCredit) {
            foreach ([1, 4, 6, 8, 10] as $pack) {
                $deepLink = 'venmo://paycharge?txn=pay'
                    . '&recipients=' . rawurlencode($venmoUser)
                    . '&note='       . rawurlencode($venmoNote)
                    . '&amount='     . number_format($pack * $ratePerCredit, 2, '.', '');
                $venmoQrUrls[$pack] = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&ecc=L&charset-source=UTF-8&data=' . urlencode($deepLink);
            }
        }

        return view('customer.credits.index', compact(
            'balance', 'ratePerCredit', 'isFirstPurchase', 'paymentMethods', 'venmoQrUrls'
        ));
    }

    public function purchase(Request $request)
    {
        $user          = auth()->user();
        $credit        = $user->credit;
        $ratePerCredit = $credit?->dollar_cost_per_credit;

        // ── Guard: reject purchase if admin has not set a credit rate
        if (!$ratePerCredit) {
            return back()->with('error', 'Credit purchasing is not yet available for your account. Please contact the administrator.');
        }

        $isFirstPurchase = !CreditPurchase::where('user_id', $user->id)->exists();

        // ── Pack selection: 1 credit for first purchase; standard packs for repeat buyers
        if ($isFirstPurchase) {
            $creditsRequested = 1;
        } else {
            $data = $request->validate([
                'credits' => ['required', Rule::in(self::REPEAT_PACKS)],
            ]);
            $creditsRequested = (int) $data['credits'];
        }

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['stripe', 'venmo', 'zelle'])],
        ]);

        if ($data['payment_method'] === 'stripe') {
            Stripe::setApiKey(config('services.stripe.secret'));

            $totalCents = (int) round($creditsRequested * $ratePerCredit * 100);

            $description = $creditsRequested === 1
                ? '1 Tutoring Credit'
                : "{$creditsRequested} Tutoring Credits";

            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => config('payments.stripe.currency', 'usd'),
                        'product_data' => ['name' => $description],
                        'unit_amount'  => $totalCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => route('customer.credits.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('customer.credits.index'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id'           => $user->id,
                    'credits_purchased' => $creditsRequested,
                    'is_first_purchase' => $isFirstPurchase ? '1' : '0',
                ],
            ]);

            return redirect($checkout_session->url);
        }

        if ($data['payment_method'] === 'venmo') {
            $username = config('payments.venmo.username');
            $url = 'https://venmo.com/'.ltrim($username, '@');
            return redirect()->away($url);
        }

        // ── Zelle: redirect with payment instructions for manual transfer
        return redirect()->route('customer.credits.index')
            ->with('payment_instructions', [
                'method'  => 'Zelle',
                'contact' => config('payments.zelle.phone'),
                'note'    => config('payments.zelle.note'),
                'amount'  => number_format($creditsRequested * $ratePerCredit, 2),
                'credits' => $creditsRequested,
            ]);
    }

    public function stripeCheckoutUrl(Request $request)
    {
        $user          = auth()->user();
        $credit        = $user->credit;
        $ratePerCredit = $credit?->dollar_cost_per_credit;

        if (!$ratePerCredit) {
            return response()->json(['error' => 'Rate not set'], 422);
        }

        $isFirstPurchase = !CreditPurchase::where('user_id', $user->id)->exists();

        if ($isFirstPurchase) {
            $creditsRequested = 1;
        } else {
            $data = $request->validate([
                'credits' => ['required', Rule::in(self::REPEAT_PACKS)],
            ]);
            $creditsRequested = (int) $data['credits'];
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $totalCents  = (int) round($creditsRequested * $ratePerCredit * 100);
        $description = $creditsRequested === 1 ? '1 Tutoring Credit' : "{$creditsRequested} Tutoring Credits";

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => config('payments.stripe.currency', 'usd'),
                    'product_data' => ['name' => $description],
                    'unit_amount'  => $totalCents,
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'success_url'    => route('customer.credits.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => route('customer.credits.index'),
            'customer_email' => $user->email,
            'metadata' => [
                'user_id'           => $user->id,
                'credits_purchased' => $creditsRequested,
                'is_first_purchase' => $isFirstPurchase ? '1' : '0',
            ],
        ]);

        return response()->json([
            'url'     => $checkout_session->url,
            'credits' => $creditsRequested,
            'amount'  => number_format($creditsRequested * $ratePerCredit, 2),
        ]);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('customer.credits.index')->with('error', 'Stripe session ID is missing.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $checkoutSession = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            return redirect()->route('customer.credits.index')->with('error', 'Unable to verify payment session.');
        }

        if ($checkoutSession->payment_status !== 'paid') {
            return redirect()->route('customer.credits.index')->with('error', 'Payment not completed yet.');
        }

        if (CreditPurchase::where('stripe_session_id', $sessionId)->exists()) {
            return redirect()->route('customer.credits.index')->with('success', 'Credits have already been applied.');
        }

        // Resolve user from metadata so this works even when the success URL is
        // opened on a different device (e.g. phone after scanning a QR code).
        $metaUserId = $checkoutSession->metadata->user_id ?? null;
        $user = $metaUserId ? User::find($metaUserId) : auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in to confirm your credits.');
        }

        // ── Stripe metadata: credit count and purchase type stored when checkout was created
        $creditsPurchased = (float) ($checkoutSession->metadata->credits_purchased ?? 1);
        $isFirstPurchase  = ($checkoutSession->metadata->is_first_purchase ?? '0') === '1';
        $totalPaid        = $checkoutSession->amount_total / 100;

        $user->credit()->firstOrCreate(['user_id' => $user->id], [
            'credit_balance'          => 0,
            'dollar_cost_per_credit'  => null,
        ]);

        $wasZeroBefore = $user->credit->credit_balance <= 0;

        $user->credit->increment('credit_balance', $creditsPurchased);
        $user->credit->refresh();

        // 1. Notify client of the balance change
        $user->notify(new CreditBalanceChanged(
            amount: $creditsPurchased,
            direction: 'credit',
            balanceAfter: $user->credit->credit_balance,
            reason: 'Payment confirmed – ' . $creditsPurchased . ' credit(s) added'
        ));

        // 2. Record purchase
        CreditPurchase::create([
            'user_id'           => $user->id,
            'amount'            => $creditsPurchased,
            'credits_purchased' => $creditsPurchased,
            'total_paid'        => $totalPaid,
            'stripe_session_id' => $sessionId,
            'type'              => 'deposit',
        ]);

        // 3. First-time purchase: notify admins
        if ($isFirstPurchase) {
            $admins = User::where('is_admin', true)->get();
            Notification::send($admins, new FirstCreditPurchase($user, $creditsPurchased, $totalPaid));
        }

        return redirect()->route('customer.credits.index')->with('success', 'Credits added successfully!');
    }

    /**
     * Customer signals to the admin: "I've sent a Venmo/Zelle payment, please confirm."
     * Stores the pending amount and method on the credit record for the admin to review.
     */
    public function notifyPayment(Request $request)
    {
        $data = $request->validate([
            'pending_amount' => ['required', 'numeric', 'min:1', 'max:9999'],
            'pending_method' => ['required', 'in:venmo,zelle,cash,other'],
        ]);

        $user = auth()->user();
        $user->credit()->firstOrCreate(
            ['user_id' => $user->id],
            ['credit_balance' => 0, 'dollar_cost_per_credit' => null]
        );
        $user->credit->update([
            'pending_payment_amount' => $data['pending_amount'],
            'pending_payment_method' => $data['pending_method'],
        ]);

        return redirect()->route('customer.credits.index')
            ->with('success', 'Your payment notification has been sent to the admin. Credits will be added once confirmed.');
    }
}
