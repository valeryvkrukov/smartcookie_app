<?php

namespace App\Notifications;

use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionCompleted extends Notification
{
    use Queueable;

    public function __construct(public TutoringSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'session_completed',
            'session_id'   => $this->session->id,
            'tutor_name'   => $this->session->tutor?->full_name,
            'student_name' => $this->session->student?->full_name,
            'subject'      => $this->session->subject,
            'date'         => $this->session->date,
            'tutor_notes'  => $this->session->tutor_notes,
            'message'      => 'Tutor submitted a session report.',
        ];
    }
}
