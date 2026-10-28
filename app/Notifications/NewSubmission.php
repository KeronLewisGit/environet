<?php

namespace App\Notifications;

use App\Contracts\Submission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the office inbox for the submission's area. Certificates, CVs and solicitation files stay in the back office. */
class NewSubmission extends Notification
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
        $mail = (new MailMessage)
            ->subject("{$s->kindLabel()} {$s->referenceNumber()}: {$s->senderName()}")
            ->greeting("New {$s->kindLabel()}, {$s->referenceNumber()}");
        foreach ($s->summary() as $label => $value) {
            $mail->line("**{$label}:** {$value}");
        }
        if ($s->senderEmail()) {
            $mail->replyTo($s->senderEmail(), $s->senderName());
        }

        return $mail->action('Open in the back office', $s->adminUrl())
            ->line('Attachments are kept on the private server and open only for staff with the right role.');
    }
}
