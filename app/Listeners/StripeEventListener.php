<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;


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
            // Сумма из Stripe (в центах) превращается в кредиты
            $amountPaid = $session->amount_total / 100;
            
            // Начисляем на баланс
            $user->credit->increment('credit_balance', $amountPaid);
            
            // Логируем для Софи (Раздел 11 ТЗ - Audit Trail)
            \Log::info("Auto-refill: {$user->full_name} added ${amountPaid} via Stripe.");
        }
    }

}
