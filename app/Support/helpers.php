<?php

use Illuminate\Support\Carbon;

if (! function_exists('enet')) {
    /** A fact from config/environet.php: enet('phone'), enet('projects'). */
    function enet(string $key, mixed $default = null): mixed
    {
        return config('environet.'.$key, $default);
    }
}

if (! function_exists('us_digits')) {
    /** E.164 digits for a North American (+1: Hawaiʻi 808, Guam 671, Colorado 303/720) number written any common way, or null. */
    function us_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return strlen($digits) === 11 && $digits[0] === '1' ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    function tel_href(?string $number): string
    {
        $digits = us_digits($number);

        return $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /** (808) 833-2225 for +1 numbers, the usual U.S. style; the input unchanged otherwise. */
    function format_phone(?string $number): string
    {
        $digits = us_digits($number);
        if (! $digits) {
            return (string) $number;
        }

        return sprintf('(%s) %s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img (WebP at 480/960/1920 wide).
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('environet.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");

        return [
            'src' => $url(960),
            'srcset' => $url(480).' 480w, '.$url(960).' 960w, '.$url(1920).' 1920w',
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}

if (! function_exists('photo_alt')) {
    /** The alt text kept with each photo in config('environet.photos'). */
    function photo_alt(string $key): string
    {
        return (string) config('environet.photos.'.$key.'.alt', '');
    }
}

if (! function_exists('format_uei')) {
    /** A SAM Unique Entity ID, upper-cased and stripped of spaces. */
    function format_uei(?string $uei): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $uei));
    }
}

if (! function_exists('file_size')) {
    /** 2.4 MB, 380 KB */
    function file_size(?int $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes >= 1_048_576 ? round($bytes / 1_048_576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    }
}

if (! function_exists('usd')) {
    /** $16.84 million / $988,585: the way award values are usually quoted. Null stays null. */
    function usd(?float $amount, bool $short = true): ?string
    {
        if ($amount === null) {
            return null;
        }
        if (! $short || $amount < 1_000_000) {
            return '$'.number_format($amount, 0);
        }

        return '$'.plain_number($amount / 1_000_000, 2).' million';
    }
}

if (! function_exists('us_date')) {
    /** October 27, 2026 (or Oct 27, 2026 when $short). Accepts Y-m-d strings and dates. */
    function us_date(DateTimeInterface|string|null $date, bool $short = false): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $d = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);

        return $d->format($short ? 'M j, Y' : 'F j, Y');
    }
}

if (! function_exists('plain_number')) {
    /** 9.50 → "9.5", 9.00 → "9": a figure without trailing zeros. */
    function plain_number(float $n, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format($n, $decimals, '.', ''), '0'), '.');
    }
}
