<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitationRequest;
use App\Models\Solicitation;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/** RFQs and bid inquiries: contracting officers and prime contractors send a request straight to business development. */
class SolicitationController extends Controller
{
    public function create(): View
    {
        return view('pages.rfq');
    }

    public function store(StoreSolicitationRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, Solicitation::fakeReference(), 'RFQ / bid inquiry')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $data = $request->safe()->except(['due_date', 'due_time', 'site_visit_date', 'site_visit_time', 'files']);
        $solicitation = Solicitation::create($data + [
            'response_due_at' => $request->dueAt(),
            'site_visit_at' => $request->siteVisitAt(),
            'source_url' => Intake::source($request),
        ]);

        /** @var list<UploadedFile> $files */
        $files = $request->file('files', []);
        foreach ($files as $file) {
            $solicitation->files()->create([
                // Random name on the private disk, one folder per solicitation.
                'path' => $file->store('solicitations/'.$solicitation->id, 'local'),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }

        $request->releaseHeldUploads();
        Intake::complete($solicitation->refresh(), $request);

        return to_route('thanks');
    }
}
