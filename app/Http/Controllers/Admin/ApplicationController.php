<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\CertificationKind;
use App\Enums\JobCategory;
use App\Enums\Office;
use App\Http\Controllers\Controller;
use App\Models\ApplicantCertification;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobApplication::class);
        $filters = $this->filters($request);
        $counts = JobApplication::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.applications.index', [
            'applications' => JobApplication::with(['jobPosting', 'certifications'])->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(JobApplication $application): View
    {
        Gate::authorize('view', $application);

        return view('admin.applications.show', ['application' => $application->load(['jobPosting', 'notes.user', 'certifications.checker'])]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['status' => ['required', Rule::enum(ApplicationStatus::class)]]);
        if ($application->moveTo($status = ApplicationStatus::from($data['status']))) {
            $application->save();
            $application->log('Status changed to '.$status->label().'.', 'status', $this->user($request));
        }

        return back()->with('status', "{$application->reference} saved.");
    }

    public function note(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $application->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    /** HR has seen the card or certificate: record who checked it, and correct the expiry if needed. */
    public function checkCertification(Request $request, JobApplication $application, ApplicantCertification $certification): RedirectResponse
    {
        Gate::authorize('update', $application);
        abort_unless($certification->job_application_id === $application->id, 404);
        $user = $this->user($request);
        $data = $request->validate(['expires_on' => [$certification->kind->needsExpiry() ? 'required' : 'nullable', 'date_format:Y-m-d']]);
        $certification->fill($data + ['checked_at' => now(), 'checked_by' => $user->id])->save();
        $application->log("Checked {$certification->kind->label()}".($certification->expires_on ? ', valid to '.$certification->expires_on->format('M j, Y') : '').'.', 'status', $user);

        return back()->with('status', $certification->kind->short().' marked as checked.');
    }

    public function cv(Request $request, JobApplication $application): StreamedResponse
    {
        Gate::authorize('openCv', $application);
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);
        $application->log('Opened the résumé.', 'file', $this->user($request));
        $name = Str::slug($application->reference.'-'.$application->full_name.'-cv').'.'.pathinfo($application->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($application->cv_path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', JobApplication::class);
        $query = JobApplication::with(['jobPosting', 'certifications'])->filter($this->filters($request));

        return $this->csv('environet-applications', ['Reference', 'Received', 'Status', 'Name', 'Position', 'Type of work', 'Experience', 'Office', 'Can travel', 'Certifications (self-declared, expiry)', 'Phone', 'Email', 'Available from', 'Resume'],
            $query->lazyByIdDesc(500)->map(fn (JobApplication $a) => [
                $a->reference, $a->created_at->format('Y-m-d'), $a->status->label(), $a->full_name, $a->position(), $a->category->label(), $a->experience->label(),
                $a->office->label(), $a->can_travel ? 'yes' : 'no',
                $a->certifications->map(fn (ApplicantCertification $c) => $c->kind->short().($c->expires_on ? ' '.$c->expires_on->format('Y-m-d') : ''))->implode('; '),
                format_phone($a->phone), $a->email, $a->available_from?->format('Y-m-d'), $a->cv_path ? 'yes' : 'no',
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $job = input_string($request->query('job'));

        return [
            'status' => ApplicationStatus::tryFrom(input_string($request->query('status')))?->value,
            'category' => JobCategory::tryFrom(input_string($request->query('category')))?->value,
            'job' => ctype_digit($job) ? $job : null,
            'waiting' => $request->boolean('waiting') ? '1' : null,
            'office' => Office::tryFrom(input_string($request->query('office')))?->value,
            'cert' => CertificationKind::tryFrom(input_string($request->query('cert')))?->value,
            'certs' => input_string($request->query('certs')) === 'lapsing' ? 'lapsing' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
