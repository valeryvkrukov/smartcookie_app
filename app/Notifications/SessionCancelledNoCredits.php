<?php

namespace App\Notifications;

use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the tutor when a session is automatically cancelled 24h before start
 * because the client has zero credits.
 */
class SessionCancelledNoCredits extends Notification
{
    use Queueable;

    public function __construct(public TutoringSession $session) {}

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
            ->subject('SmartCookie: Session Cancelled — Insufficient Credits')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('A session has been automatically cancelled because the client has insufficient credits.')
            ->line('**Student:** ' . ($this->session->student?->full_name ?? 'N/A'))
            ->line('**Subject:** ' . $this->session->subject)
            ->line('**Date:** '    . Carbon::parse($this->session->date)->format('l, F j, Y'))
            ->line('**Time:** '    . Carbon::parse($this->session->start_time)->format('g:i A'))
            ->action('View Calendar', url('/tutor/calendar'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'session_cancelled_no_credits',
            'session_id'   => $this->session->id,
            'student_name' => $this->session->student?->full_name,
            'subject'      => $this->session->subject,
            'date'         => $this->session->date,
            'start_time'   => $this->session->start_time,
            'message'      => 'Session automatically cancelled due to insufficient credits.',
        ];
    }
}
