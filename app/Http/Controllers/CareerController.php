<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(): View
    {
        return view('pages.careers.index', ['jobs' => JobPosting::open()->orderBy('category')->orderBy('title')->get()]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.careers.show', ['job' => $job]);
    }

    public function create(Request $request): View
    {
        $jobs = JobPosting::open()->orderBy('title')->get();

        return view('pages.careers.apply', ['jobs' => $jobs, 'selected' => $jobs->firstWhere('slug', input_string($request->query('job')))]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, JobApplication::fakeReference(), 'Job application')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $data = $request->safe()->except(['cv', 'consent', 'certs']);
        if ($file = $request->file('cv')) {
            // Random name on the private disk; the original name is shown to HR only.
            $data += [
                'cv_path' => $file->store('cvs', 'local'),
                'cv_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'cv_mime' => $file->getMimeType(),
                'cv_size' => (int) $file->getSize(),
            ];
        }
        $application = JobApplication::create($data + ['consented_at' => now()]);
        /** @var list<array{kind: string, detail?: string|null, expires_on?: string|null}> $certs */
        $certs = (array) $request->validated('certs', []);
        foreach ($certs as $cert) {
            $application->certifications()->create([
                'kind' => $cert['kind'],
                'detail' => input_string($cert['detail'] ?? null) ?: null,
                'expires_on' => input_string($cert['expires_on'] ?? null) ?: null,
            ]);
        }

        $request->releaseHeldUploads();
        Intake::complete($application->refresh()->load(['jobPosting', 'certifications']), $request);

        return to_route('thanks');
    }
}
