<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class NewClientRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $parent, public array $studentData) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Client Registration: ' . $this->parent->full_name)
            ->line('A new parent has registered on the portal.')
            ->line('--- Parent Details ---')
            ->line('Name: ' . $this->parent->full_name)
            ->line('Email: ' . $this->parent->email)
            ->line('Phone: ' . $this->parent->phone)
            ->line('Address: ' . $this->parent->address)
            ->line('--- Student Details ---')
            ->line('Student Name: ' . $this->studentData['student_name'])
            ->line('Grade: ' . $this->studentData['student_grade'])
            ->line('School: ' . $this->studentData['student_school'])
            ->line('Goals: ' . ($this->studentData['tutoring_goals'] ?? 'Not specified'))
            ->action('View in Admin Dashboard', url('/admin/clients/' . $this->parent->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
