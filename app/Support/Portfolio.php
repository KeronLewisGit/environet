<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The past-performance record in config/environet.php: federal awards exactly as USAspending.gov
 * publishes them (title, agency, office, obligated amount, dates). Nothing is called complete;
 * the record shows the period of performance the source gives.
 *
 * @phpstan-type Award array{slug: string, title: string, published: string, sector: string, agency: string, department: string, office: string, office_label: string, piid: string, parent_idv: string|null, value: float, signed: string, pop_start: string, pop_end: string, place: string, naics: string, naics_label: string, source: string, photo: string, featured: bool}
 */
final class Portfolio
{
    /** @return Collection<string, Award> newest signed first */
    public static function all(): Collection
    {
        /** @var array<string, array<string, mixed>> $awards */
        $awards = (array) enet('awards', []);

        /** @var Collection<string, Award> */
        return collect($awards)->map(fn (array $a, string $slug) => ['slug' => $slug] + $a)
            ->sortByDesc(fn (array $a) => $a['signed']);
    }

    /** @return Award|null */
    public static function find(string $slug): ?array
    {
        return self::all()->get($slug);
    }

    /** @return Collection<string, Award> */
    public static function featured(): Collection
    {
        return self::all()->where('featured', true);
    }

    /** @return Collection<string, Award> */
    public static function inSector(?string $sector): Collection
    {
        return $sector ? self::all()->filter(fn (array $a) => $a['sector'] === $sector) : self::all();
    }

    /** @return array<string, string> key => label, for sectors that have awards */
    public static function sectors(): array
    {
        $used = self::all()->pluck('sector')->unique()->all();

        return collect((array) enet('sectors', []))->only($used)->map(fn (array $s) => (string) $s['label'])->all();
    }

    public static function sectorLabel(string $key): string
    {
        return (string) (enet('sectors.'.$key.'.label') ?? ucfirst($key));
    }

    public static function flag(string $sector): string
    {
        return (string) (enet('sectors.'.$sector.'.flag') ?? 'navy');
    }

    /**
     * What the dates say, never more: "Period of performance to Nov 20, 2027" while it runs,
     * "Period of performance ended Dec 19, 2023" once the recorded end date has passed.
     *
     * @param  Award  $award
     */
    public static function periodLabel(array $award): string
    {
        $end = Carbon::parse($award['pop_end']);

        return $end->isFuture() ? 'Period of performance to '.us_date($end, true) : 'Period of performance ended '.us_date($end, true);
    }

    /**
     * @param  Award  $award
     */
    public static function isCurrent(array $award): bool
    {
        return Carbon::parse($award['pop_end'])->isFuture();
    }

    /**
     * The other awards to show under one: same sector first.
     *
     * @return Collection<string, Award>
     */
    public static function related(string $slug, int $limit = 3): Collection
    {
        $award = self::find($slug);

        return self::all()->except($slug)
            ->sortBy(fn (array $a) => $award && $a['sector'] === $award['sector'] ? 0 : 1)->take($limit);
    }
}
