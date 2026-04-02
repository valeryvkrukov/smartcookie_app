<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionScheduled extends Notification
{
    use Queueable;

    protected $session;
    protected $isRecurring;

    /**
     * Create a new notification instance.
     */
    public function __construct($session, $isRecurring = false)
    {
        $this->session = $session;
        $this->isRecurring = $isRecurring;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->is_subscribed) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('SmartCookie: New Session Scheduled')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('A new tutoring session has been added to your schedule.')
            ->line('**Student:** ' . $this->session->student->full_name)
            ->line('**Subject:** ' . $this->session->subject)
            ->line('**Date:** ' . \Carbon\Carbon::parse($this->session->date)->format('l, F j, Y'))
            // TIME FIX: Ensure start_time is properly formatted
            ->line('**Time:** ' . \Carbon\Carbon::parse($this->session->start_time)->format('g:i A'));

        if ($this->isRecurring) {
            $message->line('**Note:** This is a recurring weekly session (12 weeks series).');
        }

        return $message->action('View Calendar', url('/dashboard'))
            ->line('Thank you for choosing SmartCookie Tutors!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_scheduled',
            'session_id' => $this->session->id,
            'student_name' => $this->session->student->full_name,
            'subject' => $this->session->subject,
            'date' => $this->session->date,
            'start_time' => $this->session->start_time,
            'is_recurring' => $this->isRecurring,
            'message' => 'A new session has been scheduled.',
        ];
    }
}
