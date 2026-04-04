<?php

namespace App\Notifications;

use App\Models\TutoringSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionCancelledByClient extends Notification
{
    use Queueable;

    public function __construct(
        public TutoringSession $session,
        public User $cancelledBy
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
            ->subject('SmartCookie: Session Cancelled by Client')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('A session has been cancelled by the client.')
            ->line('**Student:** ' . $this->session->student?->full_name)
            ->line('**Subject:** ' . $this->session->subject)
            ->line('**Date:** ' . Carbon::parse($this->session->date)->format('l, F j, Y'))
            ->line('**Time:** ' . Carbon::parse($this->session->start_time)->format('g:i A'))
            ->line('**Cancelled by:** ' . $this->cancelledBy->full_name)
            ->action('View Calendar', url('/tutor/calendar'))
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'session_cancelled_by_client',
            'session_id'   => $this->session->id,
            'student_name' => $this->session->student?->full_name,
            'subject'      => $this->session->subject,
            'date'         => $this->session->date,
            'start_time'   => $this->session->start_time,
            'cancelled_by' => $this->cancelledBy->full_name,
            'message'      => 'Session cancelled by ' . $this->cancelledBy->full_name . '.',
        ];
    }
}
