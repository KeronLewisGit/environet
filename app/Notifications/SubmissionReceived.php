<?php

namespace App\Notifications;

use App\Contracts\Submission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation to whoever filled the form: their reference number and what happens next. */
class SubmissionReceived extends Notification
{
    public function __construct(public Submission $submission) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->submission;

        return (new MailMessage)
            ->subject("We received your {$s->kindLabel()} ({$s->referenceNumber()})")
            ->greeting('Thank you, '.$s->senderName().'.')
            ->line("Your reference number is **{$s->referenceNumber()}**. Please quote it if you contact us.")
            ->line($s->nextSteps())
            ->line('Questions: '.format_phone((string) enet('phone')).' or '.enet('email').'.')
            ->salutation('Environet, Inc., '.enet('offices.oahu.street').', Honolulu');
    }
}
