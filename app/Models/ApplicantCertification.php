<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\CertificationKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A field certification an applicant declared (HAZWOPER 40-hour, UXO Technician II, ...), with its
 * expiry date. Self-declared until HR has seen the card or certificate and marks it checked.
 *
 * @property int $id
 * @property int $job_application_id
 * @property CertificationKind $kind
 * @property string|null $detail
 * @property Carbon|null $expires_on
 * @property Carbon|null $checked_at
 * @property int|null $checked_by
 * @property-read JobApplication $application
 * @property-read User|null $checker
 */
class ApplicantCertification extends Model
{
    /** Certifications expiring within this many days are flagged. */
    public const int WARNING_DAYS = 30;

    protected $fillable = ['kind', 'detail', 'expires_on', 'checked_at', 'checked_by'];

    #[\Override]
    protected function casts(): array
    {
        return ['kind' => CertificationKind::class, 'expires_on' => 'date', 'checked_at' => 'datetime'];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /** @return BelongsTo<User, $this> */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /** valid | expiring | expired (no expiry date means valid). */
    public function state(): string
    {
        if ($this->expires_on === null) {
            return 'valid';
        }
        if ($this->expires_on->lt(today())) {
            return 'expired';
        }

        return $this->expires_on->lte(today()->addDays(self::WARNING_DAYS)) ? 'expiring' : 'valid';
    }

    public function stateLabel(): string
    {
        return match ($this->state()) {
            'expired' => 'Expired '.$this->expires_on?->format('M j, Y'),
            'expiring' => 'Expires '.$this->expires_on?->format('M j, Y'),
            default => $this->expires_on ? 'Valid to '.$this->expires_on->format('M j, Y') : 'No expiry given',
        };
    }

    public function tone(): string
    {
        return match ($this->state()) {
            'expired' => 'critical',
            'expiring' => 'attention',
            default => 'good',
        };
    }

    /**
     * Expired or expiring within WARNING_DAYS, on applications still being considered.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLapsing(Builder $query): void
    {
        $query->whereNotNull('expires_on')->whereDate('expires_on', '<=', today()->addDays(self::WARNING_DAYS))
            ->whereHas('application', fn (Builder $a) => $a->whereIn('status', ApplicationStatus::active()));
    }
}
