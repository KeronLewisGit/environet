<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A certificate filed by a vendor. Private disk only. A renewal supersedes the previous copy,
 * which is kept for the audit trail.
 *
 * @property int $id
 * @property int $vendor_id
 * @property DocumentKind $kind
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property string|null $number
 * @property Carbon|null $expires_on
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property Carbon|null $reminded_at
 * @property Carbon|null $superseded_at
 * @property Carbon $created_at
 * @property-read Vendor $vendor
 * @property-read User|null $verifier
 */
class VendorDocument extends Model
{
    protected $fillable = ['kind', 'path', 'original_name', 'mime', 'size', 'number', 'expires_on', 'verified_at', 'verified_by', 'reminded_at', 'superseded_at'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kind' => DocumentKind::class,
            'expires_on' => 'date',
            'verified_at' => 'datetime',
            'reminded_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Current certificates with an expiry date: the ones the dashboard, the register and the
     * reminders track. Supporting documents ("other") never expire, whatever date they carry.
     *
     * @param  Builder<self>  $query
     */
    public function scopeTracked(Builder $query): void
    {
        $query->whereNull('superseded_at')->whereNotNull('expires_on')
            ->whereIn('kind', array_map(fn (DocumentKind $k) => $k->value, DocumentKind::certificates()));
    }

    /** valid | expiring | expired (documents without an expiry are valid). */
    public function state(): string
    {
        if ($this->expires_on === null || ! $this->kind->expires()) {
            return 'valid';
        }
        if ($this->expires_on->lt(today())) {
            return 'expired';
        }

        return $this->expires_on->lte(today()->addDays(Vendor::EXPIRY_WARNING_DAYS)) ? 'expiring' : 'valid';
    }

    public function daysLeft(): ?int
    {
        return $this->expires_on ? (int) today()->diffInDays($this->expires_on, false) : null;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
