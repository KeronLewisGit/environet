<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitation documents sent with an invitation. Private disk only; streamed to business development staff.
 *
 * @property int $id
 * @property int $solicitation_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property-read Solicitation $solicitation
 */
class SolicitationFile extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    /** @return BelongsTo<Solicitation, $this> */
    public function solicitation(): BelongsTo
    {
        return $this->belongsTo(Solicitation::class);
    }
}
