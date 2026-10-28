<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * A public reference such as TI-26-00042: prefix, two-digit year, zero-padded id.
 * Written right after insert, because the id is part of it.
 *
 * @mixin Model
 */
trait HasReference
{
    abstract public static function referencePrefix(): string;

    protected static function bootHasReference(): void
    {
        static::creating(function (Model $model): void {
            $model->setAttribute('reference', $model->getAttribute('reference') ?? 'pending-'.bin2hex(random_bytes(4)));
        });
        static::created(function (Model $model): void {
            $model->forceFill([
                'reference' => sprintf('%s-%s-%05d', static::referencePrefix(), $model->getAttribute('created_at')->format('y'), $model->getKey()),
            ])->saveQuietly();
        });
    }

    /** The reference a bot is shown after filling the honeypot: well-formed, never stored. */
    public static function fakeReference(): string
    {
        return static::referencePrefix().'-'.now()->format('y').'-00000';
    }
}
