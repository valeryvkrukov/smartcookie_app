<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Notifications\CreditBalanceChanged;


class StripeEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        //
    }

    public function handleCheckoutSessionCompleted($event)
    {
        $session  = $event->data->object;
        $metadata = $session->metadata ?? null;

        // ── Idempotency: skip if already handled by the success() callback
        // ── Metadata signals an intentional purchase, not an external Stripe event
        if (!$metadata || !isset($metadata->credits_purchased)) {
            return;
        }

        $userId = $metadata->user_id ?? null;
        $user   = $userId ? User::find($userId) : User::where('stripe_id', $session->customer)->first();

        if (!$user) {
            return;
        }

        $creditsPurchased = (float) $metadata->credits_purchased;
        $totalPaid        = $session->amount_total / 100;

        $user->credit->increment('credit_balance', $creditsPurchased);
        $user->credit->refresh();

        $user->notify(new CreditBalanceChanged(
            amount: $creditsPurchased,
            direction: 'credit',
            balanceAfter: (float) $user->credit->credit_balance,
            reason: 'Stripe webhook payment confirmation'
        ));

        \Log::info("Stripe webhook: {$user->full_name} +{$creditsPurchased} credits (${$totalPaid} paid).");
    }

}
