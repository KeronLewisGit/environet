<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** To an approved vendor: these certificates or policies expire soon, upload the renewals with this link. */
class CertificateExpiring extends Notification
{
    /** @param Collection<int, VendorDocument> $documents */
    public function __construct(public Collection $documents, public string $renewUrl) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendor = $this->documents->first()?->vendor;
        $mail = (new MailMessage)
            ->subject('Certificate renewal for your Environet vendor registration'.($vendor ? ' ('.$vendor->reference.')' : ''))
            ->greeting('Hello'.($vendor ? ' '.$vendor->contact_name : '').',')
            ->line('To stay on our approved vendor list, please send us the renewed copy of:');
        foreach ($this->documents as $d) {
            $mail->line('- '.$d->kind->label().': '.($d->state() === 'expired' ? 'expired on ' : 'expires on ').$d->expires_on?->format('F j, Y'));
        }

        return $mail->action('Upload the renewal', $this->renewUrl)
            ->line('The link needs no password. It works for '.Vendor::RENEWAL_LINK_DAYS.' days, or until you have uploaded the renewed copies.');
    }
}
