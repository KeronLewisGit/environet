<?php

namespace App\Support;

/**
 * The places drawn on the home-page Pacific map, each with its source, and the projection that
 * puts them over resources/img/pacific-land.svg (built by scripts/map/pacific.mjs with the same
 * numbers: 115°E to 65°W, 55°N to 0°, x scaled by cos 30°, 10 units a degree).
 *
 * Kinds: office (the firm's website and SBA profile), award (a place of performance on a federal
 * award in config environet.awards, outside Hawaiʻi and Guam) and earlier (work the firm's own
 * website names: "Japan, Korea, Guam, Saipan and Yap"). Nothing else is plotted.
 *
 * @phpstan-type Place array{key: string, label: string, kind: string, lat: float, lon: float, x: float, y: float, dx?: int, dy?: int, anchor?: string}
 */
final class PacificMap
{
    public const WIDTH = 1559;

    public const HEIGHT = 550;

    /** Honolulu, where every route starts. */
    public const HUB = 'honolulu';

    /** @var list<array{key: string, label: string, kind: string, lat: float, lon: float, dx?: int, dy?: int, anchor?: string}> */
    private const PLACES = [
        ['key' => 'honolulu', 'label' => 'Honolulu', 'kind' => 'office', 'lat' => 21.31, 'lon' => -157.85, 'dx' => -14, 'dy' => -16, 'anchor' => 'end'],
        ['key' => 'kamuela', 'label' => 'Kamuela', 'kind' => 'office', 'lat' => 20.02, 'lon' => -155.67, 'dx' => 14, 'dy' => 34],
        ['key' => 'guam', 'label' => 'Guam', 'kind' => 'office', 'lat' => 13.45, 'lon' => 144.79, 'dx' => 16, 'dy' => 34],
        ['key' => 'colorado', 'label' => 'Lafayette, CO', 'kind' => 'office', 'lat' => 39.99, 'lon' => -105.09, 'dx' => 0, 'dy' => 40, 'anchor' => 'middle'],
        ['key' => 'bremerton', 'label' => 'Bremerton, WA', 'kind' => 'award', 'lat' => 47.57, 'lon' => -122.63, 'dx' => -14, 'dy' => -12, 'anchor' => 'end'],
        ['key' => 'rockville', 'label' => 'Rockville, MD', 'kind' => 'award', 'lat' => 39.08, 'lon' => -77.15, 'dx' => 0, 'dy' => 40, 'anchor' => 'middle'],
        ['key' => 'saipan', 'label' => 'Saipan', 'kind' => 'earlier', 'lat' => 15.19, 'lon' => 145.75, 'dx' => 14, 'dy' => -8],
        ['key' => 'yap', 'label' => 'Yap', 'kind' => 'earlier', 'lat' => 9.53, 'lon' => 138.13, 'dx' => -14, 'dy' => 10, 'anchor' => 'end'],
        ['key' => 'japan', 'label' => 'Japan', 'kind' => 'earlier', 'lat' => 36.2, 'lon' => 138.25, 'dx' => 14, 'dy' => 30],
        ['key' => 'korea', 'label' => 'Korea', 'kind' => 'earlier', 'lat' => 36.5, 'lon' => 127.9, 'dx' => -14, 'dy' => 10, 'anchor' => 'end'],
    ];

    /** @return array{0: float, 1: float} x, y in the map's viewBox */
    public static function project(float $lat, float $lon): array
    {
        $lon360 = $lon < -30 ? $lon + 360 : $lon;

        return [round(($lon360 - 115) * 10 * cos(M_PI / 6), 1), round((55 - $lat) * 10, 1)];
    }

    /** @return list<Place> */
    public static function places(): array
    {
        return array_map(function (array $p): array {
            [$x, $y] = self::project($p['lat'], $p['lon']);

            return $p + ['x' => $x, 'y' => $y];
        }, self::PLACES);
    }

    /**
     * A route from Honolulu to every other place, bowed north like a great-circle line, in the
     * order they draw: offices, then award sites, then earlier work.
     *
     * @return list<array{key: string, kind: string, d: string}>
     */
    public static function routes(): array
    {
        $places = collect(self::places())->keyBy('key');
        $hub = $places->get(self::HUB);
        $order = ['office' => 0, 'award' => 1, 'earlier' => 2];

        return $places->except([self::HUB, 'kamuela'])
            ->sortBy(fn (array $p) => $order[$p['kind']])
            ->map(function (array $p) use ($hub): array {
                $mx = ($hub['x'] + $p['x']) / 2;
                $my = ($hub['y'] + $p['y']) / 2;
                $lift = hypot($p['x'] - $hub['x'], $p['y'] - $hub['y']) * 0.22;

                return ['key' => $p['key'], 'kind' => $p['kind'], 'd' => sprintf('M%s %sQ%s %s %s %s', $hub['x'], $hub['y'], round($mx, 1), round($my - $lift, 1), $p['x'], $p['y'])];
            })->values()->all();
    }
}
