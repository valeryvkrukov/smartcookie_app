<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeTutorRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $tutor)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Welcome to SmartCookie Tutors')
            ->greeting('Hello, ' . $this->tutor->first_name . '!')
            ->line('Your account has been created successfully.');

        return $message
            ->action('Open Dashboard', url('/dashboard'))
            ->line('We are glad to have you with us.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome_tutor_registered',
            'tutor_id' => $this->tutor->id,
            'message' => 'Your account was created successfully.',
        ];
    }
}
