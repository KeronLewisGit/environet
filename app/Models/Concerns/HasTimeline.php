<?php

namespace App\Models\Concerns;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasTimeline
{
    /** @return MorphMany<Note, $this> */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'subject')->latest('id');
    }

    public function log(string $body, string $kind = 'system', ?User $user = null): Note
    {
        return $this->notes()->create(['kind' => $kind, 'body' => $body, 'user_id' => $user?->id]);
    }
}
