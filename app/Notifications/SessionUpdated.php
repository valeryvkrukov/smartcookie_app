<?php

namespace App\Notifications;

use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionUpdated extends Notification
{
    use Queueable;

    public function __construct(public TutoringSession $session)
    {
    }

    /**
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: Session Updated')
            ->greeting('Hello, '.$notifiable->first_name.'!')
            ->line('A tutoring session has been updated.')
            ->line('Student: '.$this->session->student?->full_name)
            ->line('Subject: '.$this->session->subject)
            ->line('Date: '.Carbon::parse($this->session->date)->format('l, F j, Y'))
            ->line('Time: '.Carbon::parse($this->session->start_time)->format('g:i A'))
            ->action('View Calendar', url('/dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_updated',
            'session_id' => $this->session->id,
            'student_name' => $this->session->student?->full_name,
            'subject' => $this->session->subject,
            'date' => $this->session->date,
            'start_time' => $this->session->start_time,
            'message' => 'A tutoring session was updated.',
        ];
    }
}
