<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * A slug for $title that no row of $model uses yet: "site-foreman", then "site-foreman-2".
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $reserved  slugs a route already uses, e.g. "apply" in /careers/apply
     */
    public static function unique(string $model, string $title, string $fallback, array $reserved = []): string
    {
        $base = Str::slug($title) ?: $fallback;
        $slug = $base;
        for ($i = 2; in_array($slug, $reserved, true) || $model::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
