<?php

namespace App\Notifications;

use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 30-hour reminder sent to the client (parent) before a scheduled session.
 */
class SessionReminder extends Notification
{
    use Queueable;

    public function __construct(
        public TutoringSession $session,
        public float $creditBalance,
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
        $date     = Carbon::parse($this->session->date)->format('l, F j, Y');
        $time     = Carbon::parse($this->session->start_time)->format('g:i A');
        $balance  = number_format($this->creditBalance, 2);

        $message = (new MailMessage)
            ->subject('SmartCookie: Session Reminder — Tomorrow')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('This is a reminder that a tutoring session is scheduled in approximately 30 hours.')
            ->line('**Student:** ' . ($this->session->student?->full_name ?? 'N/A'))
            ->line('**Tutor:** '   . ($this->session->tutor?->full_name ?? 'TBD'))
            ->line('**Subject:** ' . $this->session->subject)
            ->line('**Date:** '    . $date)
            ->line('**Time:** '    . $time)
            ->line('**Location:** ' . ($this->session->location ?: 'Online'))
            ->line('---')
            ->line('**Credit Balance:** ' . $balance . ' credits');

        if ($this->creditBalance <= 0) {
            $message
                ->line('⚠️ **Your session will be cancelled in 6 hours because you have no credits left. Please purchase more credits here:**')
                ->action('Purchase Credits Now', url('/customer/credits'));
        } else {
            $message->action('View Your Schedule', url('/customer/calendar'));
        }

        return $message->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'session_reminder',
            'session_id'    => $this->session->id,
            'student_name'  => $this->session->student?->full_name,
            'subject'       => $this->session->subject,
            'date'          => $this->session->date,
            'start_time'    => $this->session->start_time,
            'credit_balance' => $this->creditBalance,
            'message'       => 'Reminder: session scheduled for ' . Carbon::parse($this->session->date)->format('M j') . '.',
        ];
    }
}
