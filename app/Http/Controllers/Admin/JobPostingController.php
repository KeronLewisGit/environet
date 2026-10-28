<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', JobPosting::class);

        return view('admin.jobs.index', [
            'jobs' => JobPosting::withCount(['applications', 'applications as new_count' => fn ($q) => $q->where('status', 'new')])
                ->orderByRaw("case when status = 'open' then 0 when status = 'draft' then 1 else 2 end")->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', JobPosting::class);

        return view('admin.jobs.form', ['job' => new JobPosting(['status' => JobStatus::Open, 'openings' => 1, 'location' => 'Honolulu, Oʻahu'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', JobPosting::class);
        $data = $this->validated($request);
        $job = JobPosting::create($data + ['slug' => Slug::unique(JobPosting::class, $data['title'], 'job', ['apply'])]);

        return to_route('admin.jobs.index')->with('status', "“{$job->title}” saved.");
    }

    public function edit(JobPosting $job): View
    {
        Gate::authorize('update', $job);

        return view('admin.jobs.form', ['job' => $job]);
    }

    public function update(Request $request, JobPosting $job): RedirectResponse
    {
        Gate::authorize('update', $job);
        $job->update($this->validated($request));

        return to_route('admin.jobs.index')->with('status', "“{$job->title}” saved.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'location' => ['required', 'string', 'max:160'],
            'openings' => ['required', 'integer', 'min:1', 'max:500'],
            'summary' => ['required', 'string', 'max:1000'],
            'duties' => ['nullable', 'string', 'max:4000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'closes_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }
}
