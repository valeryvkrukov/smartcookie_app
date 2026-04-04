<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the client when their credit balance drops to 0.5 or below.
 */
class LowCreditBalance extends Notification
{
    use Queueable;

    public function __construct(public float $balance = 0.0) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->balance <= 0) {
            $line = 'Your tutoring credit balance has reached 0. Please top up to continue scheduling sessions.';
        } else {
            $line = 'Your tutoring credit balance is low — only ' . number_format($this->balance, 1) . ' credit remaining (enough for one 30-minute session).';
        }

        return (new MailMessage)
            ->subject('SmartCookie: Low Credit Balance')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line($line)
            ->line('Purchase more credits to keep your sessions running without interruption.')
            ->action('Purchase Credits', url('/customer/credits'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'low_credit_balance',
            'balance' => $this->balance,
            'message' => $this->balance <= 0
                ? 'Your credit balance is empty. Please top up to continue sessions.'
                : 'Low credit balance: ' . number_format($this->balance, 1) . ' credit remaining.',
        ];
    }
}
