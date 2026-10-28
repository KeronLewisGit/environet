<?php

namespace App\Models;

use App\Enums\RoomCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $document_room_id
 * @property string $title
 * @property RoomCategory $category
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property int|null $uploaded_by
 * @property int $downloads
 * @property Carbon $created_at
 * @property-read DocumentRoom $room
 * @property-read User|null $uploader
 */
class RoomDocument extends Model
{
    protected $fillable = ['title', 'category', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    #[\Override]
    protected function casts(): array
    {
        return ['category' => RoomCategory::class, 'downloads' => 'integer'];
    }

    /** @return BelongsTo<DocumentRoom, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(DocumentRoom::class, 'document_room_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'file');
    }
}
