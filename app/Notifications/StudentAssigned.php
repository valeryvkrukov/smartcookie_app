<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAssigned extends Notification
{
    use Queueable;

    public function __construct(
        protected User $student,
        protected float $hourlyPayout
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
            ->subject('SmartCookie: New Student Assigned to You')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('A new student has been assigned to you.')
            ->line('**Student:** ' . $this->student->full_name)
            ->line('**Hourly Payout:** $' . number_format($this->hourlyPayout, 2))
            ->line('You can view your upcoming sessions on your calendar.')
            ->salutation('— SmartCookie Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'student_assigned',
            'student_id'     => $this->student->id,
            'student_name'   => $this->student->full_name,
            'hourly_payout'  => $this->hourlyPayout,
            'message'        => "New student assigned: {$this->student->full_name}",
        ];
    }
}
