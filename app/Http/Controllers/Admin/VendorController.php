<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SetAside;
use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Teaming and vendor register: registrations, certificate and insurance checks, approvals and expiry tracking. */
class VendorController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vendor::class);
        $filters = $this->filters($request);
        $counts = Vendor::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.vendors.index', [
            'vendors' => Vendor::with('currentDocuments')->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 when status = 'reviewing' then 1 else 2 end")->orderBy('company_name')
                ->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'renewalCount' => Vendor::needsRenewal()->count(),
        ]);
    }

    public function show(Vendor $vendor): View
    {
        Gate::authorize('view', $vendor);

        return view('admin.vendors.show', ['vendor' => $vendor->load(['currentDocuments.verifier', 'documents', 'notes.user', 'reviewer'])]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        Gate::authorize('update', $vendor);
        $user = $this->user($request);
        $data = $request->validate(['status' => ['required', Rule::enum(VendorStatus::class)]]);
        $status = VendorStatus::from($data['status']);

        if ($status === VendorStatus::Approved && ($state = $vendor->load('currentDocuments')->complianceState()) !== 'valid' && $state !== 'expiring') {
            return back()->withErrors(['status' => 'A required certificate or policy is '.($state === 'missing' ? 'missing' : 'expired').'. Ask for the current copy before approving.']);
        }
        if ($vendor->claimStatus($status, $user)) {
            $vendor->log('Status changed to '.$status->label().'.', 'status', $user);
        }

        return back()->with('status', "{$vendor->reference} saved.");
    }

    public function verify(Request $request, Vendor $vendor, VendorDocument $document): RedirectResponse
    {
        Gate::authorize('openDocument', [$vendor, $document]);
        $user = $this->user($request);
        $data = $request->validate([
            'expires_on' => [$document->kind->expires() ? 'required' : 'nullable', 'date_format:Y-m-d'],
            'number' => ['nullable', 'string', 'max:80'],
        ]);
        if (! $document->kind->expires()) {
            $data['expires_on'] = null; // supporting documents are not tracked for expiry
        }
        $document->fill($data + ['verified_at' => now(), 'verified_by' => $user->id]);
        if ($document->isDirty('expires_on')) {
            $document->reminded_at = null; // a corrected expiry gets its own reminder
        }
        $document->save();
        $vendor->log("Checked the {$document->kind->label()}".($document->expires_on ? ', valid to '.$document->expires_on->format('M j, Y') : '').'.', 'status', $user);

        return back()->with('status', $document->kind->short().' marked as checked.');
    }

    /** Business development can note teaming conversations too, so 'view' is enough. */
    public function note(Request $request, Vendor $vendor): RedirectResponse
    {
        Gate::authorize('view', $vendor);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $vendor->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function document(Request $request, Vendor $vendor, VendorDocument $document): StreamedResponse
    {
        Gate::authorize('openDocument', [$vendor, $document]);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        $vendor->log("Opened the {$document->kind->label()} ({$document->original_name}).", 'file', $this->user($request));
        $name = Str::slug($vendor->reference.'-'.$document->kind->short()).'.'.pathinfo($document->path, PATHINFO_EXTENSION);
        $headers = ['Cache-Control' => 'private, no-store'];

        return $document->isImage() || $document->mime === 'application/pdf'
            ? Storage::disk('local')->response($document->path, $name, $headers)
            : Storage::disk('local')->download($document->path, $name, $headers);
    }

    /** The approved list with each certificate's expiry, for site and accounts staff. */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Vendor::class);
        $query = Vendor::with('currentDocuments')->filter($this->filters($request))->orderBy('company_name');

        return $this->csv('environet-vendors', ['Reference', 'Company', 'Type', 'Status', 'UEI', 'CAGE', 'Self-declared status', 'NAICS', 'Work', 'Location', 'Contact', 'Email', 'Phone', 'Liability expires', 'Workers comp expires', 'License expires', 'SBA certification expires', 'Compliance'],
            $query->lazyById(500)->map(function (Vendor $v) {
                $exp = fn (string $kind) => $v->currentDocuments->first(fn (VendorDocument $d) => $d->kind->value === $kind)?->expires_on?->format('Y-m-d') ?? '';

                return [$v->reference, $v->displayName(), $v->type->short(), $v->status->label(), $v->uei, $v->cage, $v->setAsideLabels(), $v->naics, implode('; ', $v->trades), $v->city.', '.$v->state,
                    $v->contact_name, $v->email, format_phone($v->phone), $exp('liability'), $exp('workers_comp'), $exp('license'), $exp('sba_cert'), $v->complianceState()];
            }));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $trade = input_string($request->query('trade'));
        $setAside = SetAside::tryFrom(input_string($request->query('set_aside')));

        return [
            'status' => VendorStatus::tryFrom(input_string($request->query('status')))?->value,
            'type' => VendorType::tryFrom(input_string($request->query('type')))?->value,
            'trade' => array_key_exists($trade, (array) enet('trades')) ? $trade : null,
            'set_aside' => $setAside && $setAside !== SetAside::Unrestricted ? $setAside->value : null,
            'renewal' => $request->boolean('renewal') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
