<?php

namespace App\Http\Requests;

use App\Enums\CertificationKind;
use App\Enums\Experience;
use App\Enums\JobCategory;
use App\Enums\Office;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApplicationRequest extends FormRequest
{
    use HoldsUploads;

    public const int MAX_CERTS = 8;

    public static function heldForm(): string
    {
        return 'application';
    }

    protected function heldFields(): array
    {
        return ['cv' => false];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        // When a job is picked, its category is the truth (so "professional roles need a CV" follows the job).
        $jobId = input_string($this->input('job_posting_id'));
        $job = ctype_digit($jobId) ? JobPosting::open()->find((int) $jobId) : null;
        if ($job) {
            $this->merge(['category' => $job->category->value]);
        }
        $this->merge([
            'email' => $this->filled('email') ? strtolower(input_string($this->input('email'))) : null,
            'can_travel' => $this->boolean('can_travel'),
            // Certification rows left blank (no type chosen) are dropped, so only real entries are validated.
            'certs' => array_values(array_filter((array) $this->input('certs', []), fn ($c) => is_array($c) && input_string($c['kind'] ?? '') !== '')),
        ]);
    }

    public function category(): ?JobCategory
    {
        return JobCategory::tryFrom(input_string($this->input('category')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $needsCv = $this->category()?->needsCv() ?? false;

        return [
            'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')
                ->where(fn ($q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()))],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'email' => [$needsCv ? 'required' : 'nullable', 'email:rfc', 'max:255'],
            'office' => ['required', Rule::enum(Office::class)],
            'region' => ['nullable', Rule::in((array) enet('regions'))],
            'can_travel' => ['boolean'],
            'certs' => ['nullable', 'array', 'max:'.self::MAX_CERTS],
            'certs.*.kind' => ['required', Rule::enum(CertificationKind::class)],
            'certs.*.detail' => ['nullable', 'string', 'max:120'],
            'certs.*.expires_on' => ['nullable', 'date_format:Y-m-d'],
            'experience' => ['required', Rule::enum(Experience::class)],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'available_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:2000'],
            'cv' => [$needsCv ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:'.implode(',', JobApplication::CV_TYPES)],
            'consent' => ['accepted'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $v): void {
            foreach ((array) $this->input('certs', []) as $i => $c) {
                $kind = CertificationKind::tryFrom(input_string($c['kind'] ?? ''));
                if ($kind?->needsExpiry() && input_string($c['expires_on'] ?? '') === '' && ! $v->errors()->has("certs.{$i}.expires_on")) {
                    $v->errors()->add("certs.{$i}.expires_on", 'Enter the expiry date for '.$kind->short().'. '.$kind->expiryHint());
                }
                if ($kind === CertificationKind::Other && input_string($c['detail'] ?? '') === '') {
                    $v->errors()->add("certs.{$i}.detail", 'Name the certification.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['first_name' => 'first name', 'last_name' => 'last name', 'available_from' => 'start date', 'cv' => 'résumé', 'category' => 'type of work', 'office' => 'office', 'certs.*.kind' => 'certification', 'certs.*.expires_on' => 'expiry date'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number with its area code, for example (808) 555-0123.',
            'job_posting_id.exists' => 'That opening has closed. Choose another opening, or send a general application.',
            'category.required' => 'Choose the type of work you are applying for.',
            'experience.required' => 'Choose how much experience you have.',
            'cv.required' => 'Scientist, engineering, construction management and office roles need a résumé (PDF or Word).',
            'email.required' => 'Scientist, engineering, construction management and office roles need an email address.',
            'cv.max' => 'Your résumé must be 5 MB or smaller.',
            'cv.mimes' => 'Your résumé must be a PDF or Word document.',
            'cv.uploaded' => 'Your résumé could not be uploaded. It must be 5 MB or smaller.',
            'office.required' => 'Choose the office you want to work from.',
            'certs.max' => 'List up to '.self::MAX_CERTS.' certifications; bring the rest to interview.',
            'certs.*.expires_on.date_format' => 'Enter the expiry date as a date.',
            'consent.accepted' => 'Please agree to us keeping your application on file.',
        ];
    }
}
