<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\ApplicationStatus;
use App\Enums\Area;
use App\Enums\Experience;
use App\Enums\JobCategory;
use App\Enums\Office;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int|null $job_posting_id
 * @property JobCategory $category
 * @property ApplicationStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $email
 * @property Office $office
 * @property string|null $region
 * @property bool $can_travel
 * @property Experience $experience
 * @property string|null $qualifications
 * @property Carbon|null $available_from
 * @property string|null $message
 * @property string|null $cv_path
 * @property string|null $cv_name
 * @property string|null $cv_mime
 * @property int|null $cv_size
 * @property Carbon|null $consented_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobPosting|null $jobPosting
 * @property-read Collection<int, ApplicantCertification> $certifications
 */
class JobApplication extends Model implements Submission
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory, HasReference, HasTimeline;

    public const array CV_TYPES = ['pdf', 'doc', 'docx'];

    /** New applications older than this many days count as waiting on the HR dashboard. */
    public const int REVIEW_DAYS = 3;

    protected $fillable = [
        'job_posting_id', 'category', 'status', 'first_name', 'last_name', 'phone', 'email', 'office', 'region', 'can_travel', 'experience',
        'qualifications', 'available_from', 'message', 'cv_path', 'cv_name', 'cv_mime', 'cv_size', 'consented_at', 'status_changed_at',
    ];

    public static function referencePrefix(): string
    {
        return 'JA';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'category' => JobCategory::class,
            'status' => ApplicationStatus::class,
            'experience' => Experience::class,
            'office' => Office::class,
            'can_travel' => 'boolean',
            'available_from' => 'date',
            'consented_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->status ??= ApplicationStatus::New;
        });
        static::saving(function (self $application): void {
            $application->setAttribute('phone_digits', preg_replace('/\D+/', '', $application->phone) ?: null);
        });
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /** @return HasMany<ApplicantCertification, $this> */
    public function certifications(): HasMany
    {
        return $this->hasMany(ApplicantCertification::class)->orderBy('kind');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function moveTo(ApplicationStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $this->status = $status;
        $this->status_changed_at = now();

        return true;
    }

    public function isWaiting(): bool
    {
        return $this->status === ApplicationStatus::New && $this->created_at->lt(now()->subDays(self::REVIEW_DAYS));
    }

    /** @param Builder<self> $query */
    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', ApplicationStatus::New)->where('created_at', '<', now()->subDays(self::REVIEW_DAYS));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['category'] ?? null, fn (Builder $q, $v) => $q->where('category', $v))
            ->when($f['job'] ?? null, fn (Builder $q, $v) => $q->where('job_posting_id', $v))
            ->when($f['waiting'] ?? null, fn (Builder $q) => $q->waiting())
            ->when($f['office'] ?? null, fn (Builder $q, $v) => $q->where('office', $v))
            ->when($f['cert'] ?? null, fn (Builder $q, $v) => $q->whereHas('certifications', fn (Builder $c) => $c->where('kind', $v)))
            ->when($f['certs'] ?? null, fn (Builder $q) => $q->whereHas('certifications', fn (Builder $c) => $c->whereNotNull('expires_on')->whereDate('expires_on', '<=', today()->addDays(ApplicantCertification::WARNING_DAYS))))
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }

    public function position(): string
    {
        return $this->jobPosting->title ?? $this->category->label().' (general application)';
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Job application';
    }

    public function area(): Area
    {
        return Area::Careers;
    }

    public function senderName(): string
    {
        return $this->full_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'Applicant' => $this->full_name,
            'Position' => $this->position(),
            'Experience' => $this->experience->label(),
            'Office' => $this->office->label(),
            'Certifications' => $this->certifications->map(fn (ApplicantCertification $c) => $c->kind->short().($c->expires_on ? ' (to '.$c->expires_on->format('M Y').')' : ''))->implode(', '),
            'Phone' => format_phone($this->phone),
            'Résumé attached' => $this->cv_path ? 'Yes' : 'No',
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.applications.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our HR team reads every application. If your experience and certifications match a current or upcoming project, we will call you on the number you gave us. Please have your certification cards ready.';
    }
}
