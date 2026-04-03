<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to all admins when a client makes their FIRST credit purchase.
 */
class FirstCreditPurchase extends Notification
{
    use Queueable;

    public function __construct(
        protected User $client,
        protected float $creditsPurchased,
        protected float $totalPaid
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: First Credit Purchase – ' . $this->client->full_name)
            ->greeting('Hello!')
            ->line('A client has made their first credit purchase.')
            ->line('**Client:** ' . $this->client->full_name)
            ->line('**Email:** ' . $this->client->email)
            ->line('**Credits Purchased:** ' . $this->creditsPurchased)
            ->line('**Amount Paid:** $' . number_format($this->totalPaid, 2))
            ->action('View Client in Directory', url('/admin/users/' . $this->client->id . '/edit'))
            ->salutation('— SmartCookie System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'first_credit_purchase',
            'client_id'         => $this->client->id,
            'client_name'       => $this->client->full_name,
            'credits_purchased' => $this->creditsPurchased,
            'total_paid'        => $this->totalPaid,
            'message'           => "{$this->client->full_name} made their first credit purchase ({$this->creditsPurchased} credit(s)).",
        ];
    }
}
