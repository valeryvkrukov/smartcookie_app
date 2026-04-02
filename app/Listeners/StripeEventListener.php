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
        $session = $event->data->object;
        $user = User::where('stripe_id', $session->customer)->first();

        if ($user) {
            // Convert the amount from Stripe (in cents) to credits
            $amountPaid = $session->amount_total / 100;
            
            // Increment the user's credit balance
            $user->credit->increment('credit_balance', $amountPaid);
            $user->credit->refresh();

            $user->notify(new CreditBalanceChanged(
                amount: (float) $amountPaid,
                direction: 'credit',
                balanceAfter: (float) $user->credit->credit_balance,
                reason: 'Stripe webhook payment confirmation'
            ));
            
            // Logging for debugging purposes
            \Log::info("Auto-refill: {$user->full_name} added ${amountPaid} via Stripe.");
        }
    }

}
