<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\SetAside;
use App\Enums\SolicitationStatus;
use App\Enums\SolicitationType;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Database\Factories\SolicitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An RFQ, RFP, task order request or subcontract quote request, sent by a contracting officer,
 * a prime contractor or another buyer.
 *
 * @property int $id
 * @property string $reference
 * @property SolicitationStatus $status
 * @property string $source
 * @property string $organization
 * @property string $contact_name
 * @property string|null $contact_role
 * @property string $email
 * @property string $phone
 * @property string $title
 * @property string|null $solicitation_number
 * @property SolicitationType $type
 * @property SetAside|null $set_aside
 * @property string|null $naics
 * @property string|null $region
 * @property string|null $location
 * @property string $scope
 * @property string|null $estimated_value
 * @property Carbon $response_due_at
 * @property Carbon|null $questions_due_on
 * @property Carbon|null $site_visit_at
 * @property bool $bond_required
 * @property string|null $documents_url
 * @property string|null $message
 * @property int|null $assigned_to
 * @property Carbon|null $submitted_at
 * @property Carbon|null $status_changed_at
 * @property string|null $source_url
 * @property Carbon $created_at
 * @property-read User|null $assignee
 * @property-read Collection<int, SolicitationFile> $files
 */
class Solicitation extends Model implements Submission
{
    /** @use HasFactory<SolicitationFactory> */
    use HasFactory, HasReference, HasTimeline;

    /** The dashboard counts open solicitations due within this many days. */
    public const int DUE_SOON_DAYS = 7;

    public const array SOURCES = [
        'federal' => 'Federal contracting office',
        'prime' => 'Prime contractor',
        'state' => 'State, county or territory agency',
        'private' => 'Private-sector client',
    ];

    /** Solicitation documents: what buyers usually send. */
    public const array FILE_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public const int MAX_FILES = 5;

    protected $fillable = [
        'status', 'source', 'organization', 'contact_name', 'contact_role', 'email', 'phone', 'title', 'solicitation_number',
        'type', 'set_aside', 'naics', 'region', 'location', 'scope', 'estimated_value', 'response_due_at', 'questions_due_on',
        'site_visit_at', 'bond_required', 'documents_url', 'message', 'assigned_to', 'submitted_at', 'status_changed_at', 'source_url',
    ];

    public static function referencePrefix(): string
    {
        return 'RQ';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => SolicitationStatus::class,
            'type' => SolicitationType::class,
            'set_aside' => SetAside::class,
            'response_due_at' => 'datetime',
            'questions_due_on' => 'date',
            'site_visit_at' => 'datetime',
            'bond_required' => 'boolean',
            'submitted_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $solicitation): void {
            $solicitation->status ??= SolicitationStatus::New;
        });
        static::saving(function (self $solicitation): void {
            $solicitation->setAttribute('phone_digits', preg_replace('/\D+/', '', $solicitation->phone) ?: null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<SolicitationFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(SolicitationFile::class);
    }

    public function moveTo(SolicitationStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $this->status = $status;
        $this->status_changed_at = now();
        if ($status === SolicitationStatus::Submitted) {
            $this->submitted_at ??= now();
        }

        return true;
    }

    /**
     * Moves to $status with a conditional UPDATE, so the same change sent twice at once is
     * applied (and logged) only once.
     */
    public function claimStatus(SolicitationStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $changes = ['status' => $status, 'status_changed_at' => now()];
        if ($status === SolicitationStatus::Submitted && $this->submitted_at === null) {
            $changes['submitted_at'] = now();
        }
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))
            ->update(array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $changes)) === 1;
        if ($claimed) {
            $this->forceFill($changes)->syncOriginal();
        }

        return $claimed;
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->response_due_at->isPast();
    }

    public function isDueSoon(): bool
    {
        return $this->status->isOpen() && ! $this->response_due_at->isPast() && $this->response_due_at->lte(now()->addDays(self::DUE_SOON_DAYS));
    }

    /** "3 days left", "due today", "4 days overdue": for chips and the dashboard. */
    public function dueLabel(): string
    {
        if (! $this->status->isOpen()) {
            return $this->response_due_at->format('M j, Y');
        }
        $days = (int) floor(now()->startOfDay()->diffInDays($this->response_due_at->copy()->startOfDay(), false));

        return match (true) {
            $this->response_due_at->isPast() => 'Deadline passed',
            $days === 0 => 'Due today, '.$this->response_due_at->format('g:i a'),
            $days === 1 => 'Due tomorrow',
            default => $days.' days left',
        };
    }

    public function dueTone(): string
    {
        return match (true) {
            $this->isOverdue() => 'critical',
            $this->isDueSoon() => 'attention',
            default => 'neutral',
        };
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', SolicitationStatus::open());
    }

    /**
     * Open and due between now and DUE_SOON_DAYS from now.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDueSoon(Builder $query): void
    {
        $query->open()->whereBetween('response_due_at', [now(), now()->addDays(self::DUE_SOON_DAYS)]);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $v === 'open' ? $q->open() : $q->where('status', $v))
            ->when($f['source'] ?? null, fn (Builder $q, $v) => $q->where('source', $v))
            ->when($f['assigned'] ?? null, fn (Builder $q, $v) => $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v))
            ->when($f['due'] ?? null, fn (Builder $q) => $q->dueSoon())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('title', 'like', $like)->orWhere('organization', 'like', $like)
                    ->orWhere('reference', 'like', $like)->orWhere('solicitation_number', 'like', $like)->orWhere('contact_name', 'like', $like));
            });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'RFQ / bid inquiry';
    }

    public function area(): Area
    {
        return Area::Solicitations;
    }

    public function senderName(): string
    {
        return $this->contact_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'From' => $this->organization.' ('.(self::SOURCES[$this->source] ?? $this->source).')',
            'Title' => $this->title,
            'Solicitation number' => (string) $this->solicitation_number,
            'Type' => $this->type->label(),
            'Set-aside' => (string) $this->set_aside?->label(),
            'NAICS' => (string) $this->naics,
            'Place of performance' => trim(($this->location ?? '').($this->region ? ', '.$this->region : ''), ', '),
            'Response due' => $this->response_due_at->format('l, F j, Y, g:i a').' HST',
            'Contact' => $this->contact_name.' · '.format_phone($this->phone).' · '.$this->email,
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.solicitations.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our business development team reviews every request and will confirm by email whether Environet will respond, well before the '.$this->response_due_at->format('F j').' deadline.';
    }
}
