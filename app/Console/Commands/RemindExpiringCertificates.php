<?php

namespace App\Console\Commands;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Notifications\CertificateExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily: emails each approved vendor whose insurance, license or SBA certification expires within
 * Vendor::EXPIRY_WARNING_DAYS a signed link to upload the renewal. One reminder per certificate.
 */
class RemindExpiringCertificates extends Command
{
    protected $signature = 'app:remind-expiring {--dry-run : List who would be emailed without sending}';

    protected $description = 'Email approved vendors a renewal link before a certificate or policy expires';

    public function handle(): int
    {
        $docs = VendorDocument::with('vendor')->tracked()->whereNull('reminded_at')
            ->whereDate('expires_on', '<=', today()->addDays(Vendor::EXPIRY_WARNING_DAYS))
            ->whereHas('vendor', fn ($q) => $q->where('status', VendorStatus::Approved))->get();

        foreach ($docs->groupBy('vendor_id') as $group) {
            /** @var VendorDocument $first */
            $first = $group->first();
            $vendor = $first->vendor;
            $this->line("{$vendor->reference} {$vendor->company_name}: ".$group->map(fn (VendorDocument $d) => $d->kind->short())->implode(', '));
            if ($this->option('dry-run')) {
                continue;
            }
            // Claim each certificate before emailing (conditional UPDATE), so two overlapping runs
            // never send the same reminder twice.
            $group = $group->filter(fn (VendorDocument $d) => VendorDocument::whereKey($d->id)->whereNull('reminded_at')->update(['reminded_at' => now()]) === 1)->values();
            if ($group->isEmpty()) {
                continue;
            }
            $url = $vendor->renewalUrl($group);
            // Sent straight away: a console run has no response to defer behind.
            Notification::route('mail', [$vendor->email])->notify(new CertificateExpiring($group, $url));
            $vendor->log('Renewal reminder emailed for '.$group->map(fn (VendorDocument $d) => $d->kind->short())->implode(', ').'.');
        }
        $this->info($docs->count().' certificate(s) to renew.');

        return self::SUCCESS;
    }
}
