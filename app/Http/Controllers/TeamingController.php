<?php

namespace App\Http\Controllers;

use App\Enums\DocumentKind;
use App\Enums\VendorStatus;
use App\Http\Requests\StoreVendorRequest;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Notifications\NewSubmission;
use App\Support\Intake;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** Teaming partner, subcontractor and vendor registration, and the signed renewal link sent before a certificate expires. */
class TeamingController extends Controller
{
    public function create(): View
    {
        return view('pages.teaming');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, Vendor::fakeReference(), 'Teaming and vendor registration')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $vendor = Vendor::create($request->safe()->except(['docs', 'consent']) + [
            'consented_at' => now(),
            'source_url' => Intake::source($request),
        ]);
        foreach (DocumentKind::cases() as $kind) {
            $this->storeDocument($vendor, $kind, $request);
        }

        $request->releaseHeldUploads();
        Intake::complete($vendor->refresh(), $request);

        return to_route('thanks');
    }

    /** The renewal page, reached only through the signed link in a CertificateExpiring email. */
    public function renew(Request $request, Vendor $vendor): View
    {
        $this->guardRenewal($request, $vendor);
        // The form posts to a link signed for the same time as the one in the email.
        $action = URL::temporarySignedRoute('teaming.renew.store', Carbon::createFromTimestamp((int) $request->query('expires')), ['vendor' => $vendor, 'for' => input_string($request->query('for'))]);

        return view('pages.teaming-renew', ['vendor' => $vendor->load('currentDocuments'), 'action' => $action]);
    }

    public function storeRenewal(Request $request, Vendor $vendor): RedirectResponse
    {
        $seen = $this->guardRenewal($request, $vendor);
        $request->validate([
            'docs' => ['required', 'array'],
            'docs.*' => ['array'],
            'docs.*.file' => ['nullable', 'file', 'max:5120', 'mimes:'.implode(',', Vendor::FILE_TYPES)],
            'docs.*.number' => ['nullable', 'string', 'max:80'],
            'docs.*.expires_on' => ['nullable', 'required_with:docs.*.file', 'date_format:Y-m-d', 'after:today'],
        ], [
            'docs.*.expires_on.after' => 'That certificate has already expired. Upload the renewed one.',
            'docs.*.expires_on.required_with' => 'Enter the expiry date of each certificate you upload.',
            'docs.*.file.max' => 'Each file must be 5 MB or smaller.',
            'docs.*.file.mimes' => 'Certificates must be PDF, JPG or PNG files.',
        ]);
        $stored = $raced = 0;
        foreach (DocumentKind::certificates() as $kind) {
            if (! $request->file('docs.'.$kind->value.'.file') instanceof UploadedFile) {
                continue;
            }
            // Claim the copy being replaced first: if the form was sent twice at once, only one wins.
            if (! $vendor->supersedeCurrent($kind, $seen)) {
                $raced++;

                continue;
            }
            $this->storeDocument($vendor, $kind, $request);
            $stored++;
        }
        if ($stored === 0) {
            return $raced > 0 ? to_route('thanks') : back()->withErrors(['docs' => 'Choose at least one renewed certificate to upload.']);
        }

        $vendor->log("Vendor uploaded {$stored} renewed ".str('certificate')->plural($stored).' through the renewal link.');
        Notify::office(new NewSubmission($vendor), $vendor->area());
        Intake::remember($request, $vendor->reference, 'Certificate renewal');

        return to_route('thanks');
    }

    /**
     * A renewal link works only for a vendor still on the books, and only until the certificates
     * it was sent for have been renewed: once used, the same email cannot replace them again.
     *
     * @return Collection<int, VendorDocument> the certificates the link was sent for, still current
     */
    private function guardRenewal(Request $request, Vendor $vendor): Collection
    {
        abort_if($vendor->status === VendorStatus::Declined, 403, 'This renewal link is no longer active. Please contact our procurement team.');
        $pending = $vendor->awaitingRenewal(input_string($request->query('for')));
        abort_if($pending->isEmpty(), 403, 'This renewal link has already been used. If you need to send another certificate, contact our procurement team for a new link.');

        return $pending;
    }

    private function storeDocument(Vendor $vendor, DocumentKind $kind, Request $request): ?VendorDocument
    {
        $file = $request->file('docs.'.$kind->value.'.file');
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $vendor->documents()->create([
            'kind' => $kind,
            'path' => $file->store('vendors/'.$vendor->id, 'local'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'number' => input_string($request->input('docs.'.$kind->value.'.number')) ?: null,
            // Only certificates expire; a date sent with a supporting document is ignored.
            'expires_on' => $kind->expires() ? (input_string($request->input('docs.'.$kind->value.'.expires_on')) ?: null) : null,
        ]);
    }
}
