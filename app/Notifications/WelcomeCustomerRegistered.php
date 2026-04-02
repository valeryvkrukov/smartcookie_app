<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomerRegistered extends Notification
{
    use Queueable;

    public function __construct(public User $parent, public ?string $studentName = null)
    {
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
        $message = (new MailMessage)
            ->subject('Welcome to SmartCookie Tutors')
            ->greeting('Hello, '.$this->parent->first_name.'!')
            ->line('Your account has been created successfully.');

        if ($this->studentName) {
            $message->line('Student profile added: '.$this->studentName);
        }

        return $message
            ->action('Open Dashboard', url('/dashboard'))
            ->line('We are glad to have you with us.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome_customer_registered',
            'parent_id' => $this->parent->id,
            'student_name' => $this->studentName,
            'message' => 'Your account was created successfully.',
        ];
    }
}
