<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the client when the admin sets or updates their per-credit rate,
 * enabling them to purchase credits for the first time.
 */
class ClientRateSet extends Notification
{
    use Queueable;

    public function __construct(public float $ratePerCredit) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: Your Account Is Ready — You Can Now Purchase Credits')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('Great news — your tutoring account has been configured and you can now purchase session credits.')
            ->line('**Your rate: $' . number_format($this->ratePerCredit, 2) . ' per credit**')
            ->line('Each credit covers one 60-minute session (0.5 credits = 30 min, 1.5 credits = 90 min).')
            ->action('Purchase Credits Now', url('/customer/credits'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'client_rate_set',
            'rate_per_credit' => $this->ratePerCredit,
            'message'         => 'Your account is ready. Purchase credits at $' . number_format($this->ratePerCredit, 2) . '/credit.',
        ];
    }
}
