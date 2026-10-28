<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Files a visitor already attached, kept on the private disk when the form comes back with a
 * validation error, so they need not attach everything again. Keyed to the visitor's session
 * (never to anything in the request), offered again for up to HOURS, then pruned.
 *
 * Session shape: held_uploads.{form}.{field} => list of ['path', 'name', 'mime', 'size', 'at'].
 */
final class HeldUploads
{
    public const string DIR = 'held';

    public const int HOURS = 24;

    /** @return list<array{path: string, name: string, mime: string|null, size: int, at: int}> */
    public static function get(string $form, string $field): array
    {
        $items = session('held_uploads.'.$form.'.'.self::slot($field), []);
        $fresh = array_filter(is_array($items) ? $items : [], fn ($i) => is_array($i) && ($i['at'] ?? 0) > now()->subHours(self::HOURS)->getTimestamp()
            && Storage::disk('local')->exists((string) ($i['path'] ?? '')));

        return array_values($fresh);
    }

    /**
     * Names to show next to the file input: "Attached: tcc-2026.pdf".
     *
     * @return list<string>
     */
    public static function names(string $form, string $field): array
    {
        return array_map(fn (array $i) => $i['name'], self::get($form, $field));
    }

    /** @return list<UploadedFile> the held files as uploads, ready for validation and store() */
    public static function files(string $form, string $field): array
    {
        return array_map(
            fn (array $i) => new UploadedFile(Storage::disk('local')->path($i['path']), $i['name'], $i['mime'], null, true),
            self::get($form, $field),
        );
    }

    /**
     * Keep $files for $field (replacing whatever was held for it before).
     *
     * @param  list<UploadedFile>  $files
     */
    public static function hold(string $form, string $field, array $files): void
    {
        $before = self::get($form, $field);
        $keep = [];
        foreach ($files as $file) {
            $existing = collect($before)->first(fn (array $i) => Storage::disk('local')->path($i['path']) === $file->getPathname());
            if ($existing) {
                $keep[] = $existing;

                continue;
            }
            $path = $file->store(self::DIR.'/'.self::token(), 'local');
            if ($path !== false) {
                $keep[] = ['path' => $path, 'name' => mb_substr($file->getClientOriginalName(), 0, 200), 'mime' => $file->getMimeType(), 'size' => (int) $file->getSize(), 'at' => now()->getTimestamp()];
            }
        }
        $kept = array_column($keep, 'path');
        foreach ($before as $old) {
            if (! in_array($old['path'], $kept, true)) {
                Storage::disk('local')->delete($old['path']);
            }
        }
        $keep === [] ? session()->forget('held_uploads.'.$form.'.'.self::slot($field)) : session()->put('held_uploads.'.$form.'.'.self::slot($field), $keep);
    }

    /** The submission went through: the files have been stored with it, so drop the held copies. */
    public static function release(string $form): void
    {
        foreach ((array) session('held_uploads.'.$form, []) as $items) {
            foreach ((array) $items as $i) {
                if (is_array($i) && isset($i['path'])) {
                    Storage::disk('local')->delete((string) $i['path']);
                }
            }
        }
        session()->forget('held_uploads.'.$form);
    }

    /** Held files older than HOURS, from visitors who never came back. Returns how many were deleted. */
    public static function prune(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(self::HOURS)->getTimestamp();
        $deleted = 0;
        foreach ($disk->allFiles(self::DIR) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }
        foreach ($disk->directories(self::DIR) as $dir) {
            if ($disk->allFiles($dir) === []) {
                $disk->deleteDirectory($dir);
            }
        }

        return $deleted;
    }

    /** Dot keys ("docs.tcc.file") would nest in the session; keep each field one slot. */
    private static function slot(string $field): string
    {
        return str_replace('.', '__', $field);
    }

    private static function token(): string
    {
        $token = session('held_uploads_token');
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            $token = Str::random(32);
            session()->put('held_uploads_token', $token);
        }

        return $token;
    }
}
