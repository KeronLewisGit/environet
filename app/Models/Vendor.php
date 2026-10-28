<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\DocumentKind;
use App\Enums\SetAside;
use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * A teaming partner, subcontractor or supplier registering with (or approved by) Environet.
 *
 * @property int $id
 * @property string $reference
 * @property VendorStatus $status
 * @property VendorType $type
 * @property string $company_name
 * @property string|null $trading_as
 * @property string|null $uei
 * @property string|null $cage
 * @property string|null $naics
 * @property list<string>|null $set_asides
 * @property int|null $year_established
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string $contact_name
 * @property string|null $contact_role
 * @property string $email
 * @property string $phone
 * @property list<string> $trades
 * @property list<string>|null $regions_served
 * @property string|null $employees
 * @property string|null $bonding
 * @property string|null $experience
 * @property string|null $message
 * @property int|null $reviewed_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $consented_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read User|null $reviewer
 * @property-read Collection<int, VendorDocument> $documents
 * @property-read Collection<int, VendorDocument> $currentDocuments
 */
class Vendor extends Model implements Submission
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory, HasReference, HasTimeline;

    /** Certificates expiring within this many days are flagged (and the vendor is reminded). */
    public const int EXPIRY_WARNING_DAYS = 30;

    /** How long the signed link in a renewal reminder works. */
    public const int RENEWAL_LINK_DAYS = 30;

    public const array FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const array EMPLOYEE_BANDS = ['1-5' => '1 to 5', '6-20' => '6 to 20', '21-50' => '21 to 50', '51-200' => '51 to 200', '200+' => 'Over 200'];

    protected $fillable = [
        'status', 'type', 'company_name', 'trading_as', 'uei', 'cage', 'naics', 'set_asides', 'year_established', 'address', 'city', 'state',
        'contact_name', 'contact_role', 'email', 'phone', 'trades', 'regions_served', 'employees', 'bonding',
        'experience', 'message', 'reviewed_by', 'approved_at', 'consented_at', 'status_changed_at', 'source_url',
    ];

    public static function referencePrefix(): string
    {
        return 'TP';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'type' => VendorType::class,
            'trades' => 'array',
            'regions_served' => 'array',
            'set_asides' => 'array',
            'approved_at' => 'datetime',
            'consented_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $vendor): void {
            $vendor->status ??= VendorStatus::New;
        });
        static::saving(function (self $vendor): void {
            $vendor->setAttribute('phone_digits', preg_replace('/\D+/', '', $vendor->phone) ?: null);
        });
    }

    /** @return HasMany<VendorDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class)->orderByDesc('id');
    }

    /** @return HasMany<VendorDocument, $this> */
    public function currentDocuments(): HasMany
    {
        return $this->hasMany(VendorDocument::class)->whereNull('superseded_at')->orderBy('kind');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function current(DocumentKind $kind): ?VendorDocument
    {
        return $this->currentDocuments->first(fn (VendorDocument $d) => $d->kind === $kind);
    }

    /**
     * The state of every required certificate.
     *
     * @return list<array{kind: DocumentKind, document: VendorDocument|null, state: string}>
     */
    public function compliance(): array
    {
        return array_map(function (DocumentKind $kind) {
            $doc = $this->current($kind);

            return ['kind' => $kind, 'document' => $doc, 'state' => $doc ? $doc->state() : 'missing'];
        }, $this->type->requiredDocuments());
    }

    /** Worst state across required certificates: missing > expired > expiring > valid. */
    public function complianceState(): string
    {
        $states = array_column($this->compliance(), 'state');
        foreach (['missing', 'expired', 'expiring'] as $s) {
            if (in_array($s, $states, true)) {
                return $s;
            }
        }

        return 'valid';
    }

    public function nextExpiry(): ?Carbon
    {
        return $this->currentDocuments->filter(fn (VendorDocument $d) => $d->expires_on !== null && $d->kind->expires())->min('expires_on');
    }

    /**
     * The signed link a renewal reminder carries. It names the certificates it was sent for and
     * stops working once all of them have been replaced, or after RENEWAL_LINK_DAYS.
     *
     * @param  iterable<VendorDocument>  $documents
     */
    public function renewalUrl(iterable $documents, ?\DateTimeInterface $until = null): string
    {
        $ids = collect($documents)->map(fn (VendorDocument $d) => $d->id)->sort()->implode('.');

        return URL::temporarySignedRoute('teaming.renew', $until ?? now()->addDays(self::RENEWAL_LINK_DAYS), ['vendor' => $this, 'for' => $ids]);
    }

    /**
     * Of the certificates a renewal link was sent for ("12.15"), those still current, i.e. not yet renewed.
     *
     * @return Collection<int, VendorDocument>
     */
    public function awaitingRenewal(string $for): Collection
    {
        $ids = array_values(array_filter(array_map('intval', explode('.', $for))));

        return $ids === [] ? new Collection : $this->documents()->whereIn('id', $ids)->whereNull('superseded_at')->get();
    }

    /**
     * Moves to $status with a conditional UPDATE (only if nobody changed the status since this
     * record was loaded), so a double-click or two officers at once approve it only once.
     */
    public function claimStatus(VendorStatus $status, ?User $by = null): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $now = now();
        $changes = ['status' => $status, 'status_changed_at' => $now, 'reviewed_by' => $by !== null ? $by->id : $this->reviewed_by];
        if ($status === VendorStatus::Approved) {
            $changes['approved_at'] = $now;
        }
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))
            ->update(array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $changes)) === 1;
        if ($claimed) {
            $this->forceFill($changes)->syncOriginal();
        }

        return $claimed;
    }

    /**
     * Marks the current copy of $kind as superseded, ahead of storing its renewal. $seen is what
     * the request found current when it checked the link: claiming exactly those rows with a
     * conditional UPDATE means that, when the same renewal is sent twice at once, only one wins.
     *
     * @param  iterable<VendorDocument>  $seen
     */
    public function supersedeCurrent(DocumentKind $kind, iterable $seen = []): bool
    {
        $ids = collect($seen)->filter(fn (VendorDocument $d) => $d->kind === $kind)->map(fn (VendorDocument $d) => $d->id)->values();
        if ($ids->isEmpty()) {
            // A certificate the link was not sent for: replace whatever is current.
            $ids = $this->documents()->where('kind', $kind)->whereNull('superseded_at')->pluck('id');
            if ($ids->isEmpty()) {
                return true;
            }
        }

        return VendorDocument::whereIn('id', $ids)->whereNull('superseded_at')->update(['superseded_at' => now()]) === $ids->count();
    }

    public function moveTo(VendorStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $this->status = $status;
        $this->status_changed_at = now();
        $this->approved_at = $status === VendorStatus::Approved ? now() : $this->approved_at;

        return true;
    }

    /**
     * Approved vendors with a current certificate expired or expiring within EXPIRY_WARNING_DAYS.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsRenewal(Builder $query): void
    {
        $query->where('status', VendorStatus::Approved)->whereHas('documents', fn (Builder $d) => $d->tracked()
            ->whereDate('expires_on', '<=', today()->addDays(self::EXPIRY_WARNING_DAYS)));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($f['set_aside'] ?? null, fn (Builder $q, $v) => $q->where('set_asides', 'like', '%"'.str_replace(['%', '_', '"'], '', (string) $v).'"%'))
            ->when($f['trade'] ?? null, fn (Builder $q, $v) => $q->where('trades', 'like', '%"'.str_replace(['%', '_', '"'], '', (string) $v).'"%'))
            ->when($f['renewal'] ?? null, fn (Builder $q) => $q->needsRenewal())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('company_name', 'like', $like)->orWhere('trading_as', 'like', $like)->orWhere('reference', 'like', $like)
                        ->orWhere('uei', 'like', $like)->orWhere('cage', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)->orWhere('email', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }

    public function displayName(): string
    {
        return $this->company_name.($this->trading_as ? ' dba '.$this->trading_as : '');
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Teaming and vendor registration';
    }

    public function area(): Area
    {
        return Area::Teaming;
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
        return [
            'Company' => $this->displayName(),
            'Type' => $this->type->label(),
            'UEI' => (string) $this->uei,
            'CAGE' => (string) $this->cage,
            'Self-declared status' => $this->setAsideLabels() ?: 'None declared',
            'Work' => implode(', ', array_map(fn (string $t) => (string) (enet('trades.'.$t) ?? $t), $this->trades)),
            'Contact' => $this->contact_name.' · '.format_phone($this->phone).' · '.$this->email,
        ];
    }

    /** "HUBZone, SDVOSB": what the company declared about itself. */
    public function setAsideLabels(bool $short = true): string
    {
        return collect($this->set_asides ?? [])->map(fn (string $v) => SetAside::tryFrom($v))->filter()
            ->map(fn (SetAside $s) => $short ? $s->short() : $s->label())->implode(', ');
    }

    public function adminUrl(): string
    {
        return route('admin.vendors.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our procurement team checks your insurance and certificates, and confirms your SAM registration, before approving your company. We will remind you before a certificate expires, with a link to upload the renewal.';
    }
}
