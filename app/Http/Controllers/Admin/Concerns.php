<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Small helpers every back-office controller uses. */
trait Concerns
{
    protected function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** Text starting with = + - @ would run as a formula in Excel, so it gets a leading quote. */
    protected static function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * @param  list<string>  $header
     * @param  iterable<int, list<mixed>>  $rows
     */
    protected function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF"); // byte-order mark so Excel reads accents correctly
            fputcsv($out, $header, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::csvSafe(...), $row), escape: '');
            }
            fclose($out);
        }, $name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
