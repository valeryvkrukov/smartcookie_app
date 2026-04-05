<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to all admins when an admin manually confirms a Venmo/Zelle payment
 * and applies credits to a customer's balance.
 */
class ManualPaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        protected User $client,
        protected float $creditsPurchased,
        protected float $totalPaid,
        protected string $paymentMethod,
        protected string $note,
        protected string $confirmedByName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'manual_payment_confirmed',
            'client_id'         => $this->client->id,
            'client_name'       => $this->client->full_name,
            'credits_purchased' => $this->creditsPurchased,
            'total_paid'        => $this->totalPaid,
            'payment_method'    => $this->paymentMethod,
            'note'              => $this->note,
            'confirmed_by'      => $this->confirmedByName,
            'message'           => "{$this->confirmedByName} confirmed {$this->paymentMethod} payment of \${$this->totalPaid} from {$this->client->full_name} ({$this->creditsPurchased} credit(s)).",
        ];
    }
}
