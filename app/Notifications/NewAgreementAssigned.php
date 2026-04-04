<?php

namespace App\Notifications;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAgreementAssigned extends Notification
{
    use Queueable;

    public function __construct(protected Agreement $agreement) {}

    // ── Channels: mail + database for all recipients
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    // ── Mail: inform client a new agreement awaits their signature
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SmartCookie: New Agreement Ready for Your Signature')
            ->greeting('Hello, ' . $notifiable->first_name . '!')
            ->line('A new document has been assigned to you and requires your signature:')
            ->line('**' . $this->agreement->name . '**')
            ->line('Please log in to the portal to review and sign the document at your earliest convenience.')
            ->action('Review & Sign', route('customer.agreements.index'))
            ->line('If you have any questions, please don\'t hesitate to reach out.')
            ->line('Thank you, SmartCookie Tutors');
    }

    // ── Database: payload for system logs feed
    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'agreement_assigned',
            'message'        => 'New agreement assigned: ' . $this->agreement->name,
            'agreement_id'   => $this->agreement->id,
            'agreement_name' => $this->agreement->name,
            'pdf_filename'   => basename($this->agreement->pdf_path),
        ];
    }
}
