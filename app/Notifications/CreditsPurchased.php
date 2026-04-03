<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every tutor assigned to any of the client's students
 * when the client purchases credits.
 */
class CreditsPurchased extends Notification
{
    use Queueable;

    public function __construct(
        protected User $client,
        protected float $creditsPurchased
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->is_subscribed) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: Client Purchased Credits – Schedule Sessions')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line("{$this->client->full_name} has just purchased {$this->creditsPurchased} credit(s).")
            ->line("Now is a great time to schedule upcoming sessions with their student(s).")
            ->action('Go to My Calendar', url('/tutor/calendar'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'credits_purchased',
            'client_id'         => $this->client->id,
            'client_name'       => $this->client->full_name,
            'credits_purchased' => $this->creditsPurchased,
            'message'           => "{$this->client->full_name} purchased {$this->creditsPurchased} credit(s). Consider scheduling sessions.",
        ];
    }
}
