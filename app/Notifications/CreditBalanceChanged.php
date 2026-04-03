<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditBalanceChanged extends Notification
{
    use Queueable;

    public function __construct(
        public float $amount,
        public string $direction,
        public float $balanceAfter,
        public string $reason
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sign = $this->direction === 'credit' ? '+' : '-';

        return (new MailMessage)
            ->subject('SmartCookie: Credit Balance Update')
            ->greeting('Hello, '.$notifiable->first_name.'!')
            ->line('Your tutoring credit balance has changed.')
            ->line('Reason: '.$this->reason)
            ->line('Credits: '.$sign.number_format($this->amount, 2))
            ->line('Current balance: '.number_format($this->balanceAfter, 2).' credit(s)')
            ->action('View Credits', url('/customer/credits'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_balance_changed',
            'direction' => $this->direction,
            'amount' => $this->amount,
            'balance_after' => $this->balanceAfter,
            'reason' => $this->reason,
            'message' => 'Your credit balance was updated.',
        ];
    }
}
