<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the client when their credit balance reaches 0.
 */
class LowCreditBalance extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: Your Credit Balance is Empty')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('Your tutoring credit balance has reached 0.')
            ->line('To continue scheduling sessions, please purchase more credits.')
            ->action('Purchase Credits', url('/customer/credits'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'low_credit_balance',
            'balance' => 0,
            'message' => 'Your credit balance has reached 0. Please top up to continue sessions.',
        ];
    }
}
