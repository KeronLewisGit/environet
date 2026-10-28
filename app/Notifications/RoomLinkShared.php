<?php

namespace App\Notifications;

use App\Models\DocumentRoom;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sends a client the signed link to their project document room. */
class RoomLinkShared extends Notification
{
    public function __construct(public DocumentRoom $room, public string $url, public string $senderName) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project documents: '.$this->room->name)
            ->greeting('Hello'.($this->room->client_contact ? ' '.$this->room->client_contact : '').',')
            ->line("{$this->senderName} has shared the document room for **{$this->room->name}** with you.")
            ->action('Open the documents', $this->url)
            ->line('The link works until '.$this->room->link_expires_at?->format('F j, Y').' and needs no password. Please do not forward it outside your organization.');
    }
}
