<?php

namespace App\Models;

use App\Models\Concerns\HasTimeline;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A client's project document room. The client opens it with an expiring signed link; the link
 * also carries the room's access key, so "revoke" (a new key) kills every link sent before.
 *
 * @property int $id
 * @property string $public_id
 * @property string $access_key
 * @property string $name
 * @property string $client_name
 * @property string|null $client_contact
 * @property string|null $client_email
 * @property string|null $description
 * @property Carbon|null $link_expires_at
 * @property Carbon|null $last_opened_at
 * @property int|null $created_by
 * @property bool $is_demo
 * @property Carbon $created_at
 * @property-read Collection<int, RoomDocument> $documents
 * @property-read User|null $creator
 */
class DocumentRoom extends Model
{
    use HasTimeline;

    public const array LINK_DAYS = [7, 30, 90];

    public const array FILE_TYPES = ['pdf', 'dwg', 'dxf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];

    protected $fillable = ['name', 'client_name', 'client_contact', 'client_email', 'description', 'link_expires_at', 'last_opened_at', 'created_by', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return ['link_expires_at' => 'datetime', 'last_opened_at' => 'datetime', 'is_demo' => 'boolean'];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $room): void {
            $room->public_id = strtolower((string) Str::ulid());
            $room->access_key = Str::random(40);
        });
    }

    /** @return HasMany<RoomDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(RoomDocument::class)->orderBy('category')->orderByDesc('id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** A new signed link valid for $days, and the expiry stored for display. */
    public function shareLink(int $days): string
    {
        $this->link_expires_at = now()->addDays($days);
        $this->save();

        return $this->signedUrl();
    }

    /** The current link (same expiry), or null when none is live. */
    public function signedUrl(): ?string
    {
        if (! $this->linkIsLive()) {
            return null;
        }

        return URL::temporarySignedRoute('rooms.show', $this->link_expires_at, ['room' => $this->public_id, 'key' => $this->access_key]);
    }

    /**
     * A download link that expires with the share link the client opened ($until), never later,
     * so a short link sent to one person does not hand out downloads that outlive it.
     */
    public function documentUrl(RoomDocument $document, ?\DateTimeInterface $until = null): string
    {
        $expires = $this->link_expires_at ?? now()->addHour();
        if ($until !== null && $until < $expires) {
            $expires = $until;
        }

        return URL::temporarySignedRoute('rooms.download', $expires, ['room' => $this->public_id, 'key' => $this->access_key, 'document' => $document->id]);
    }

    public function linkIsLive(): bool
    {
        return $this->link_expires_at !== null && $this->link_expires_at->isFuture();
    }

    /** Revoke every link already sent: a new key, no expiry. */
    public function revoke(): void
    {
        $this->access_key = Str::random(40);
        $this->link_expires_at = null;
        $this->save();
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
