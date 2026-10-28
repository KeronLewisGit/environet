<?php

namespace App\Support;

use App\Contracts\Submission;
use App\Notifications\NewSubmission;
use App\Notifications\SubmissionReceived;
use Illuminate\Http\Request;

/**
 * Every public form ends here, after its record and files are saved: a first timeline entry,
 * an email to the right office inbox, a confirmation with the reference to the sender, and the
 * reference kept in the session for the thank-you page.
 */
final class Intake
{
    public static function complete(Submission $submission, Request $request): void
    {
        $submission->log('Received from the website.');

        Notify::office(new NewSubmission($submission), $submission->area());
        Notify::sender($submission->senderEmail(), new SubmissionReceived($submission));

        self::remember($request, $submission->referenceNumber(), $submission->kindLabel());
    }

    /** Kept (not flashed) so refreshing the thank-you page still shows the number. */
    public static function remember(Request $request, string $reference, string $kind): void
    {
        $request->session()->put('last_submission', ['reference' => $reference, 'kind' => $kind]);
    }

    /** Bots fill the hidden "website" field. Pretend it worked and keep nothing. */
    public static function isBot(Request $request, string $fakeReference, string $kind): bool
    {
        if ($request->filled('website')) {
            self::remember($request, $fakeReference, $kind);

            return true;
        }

        return false;
    }

    /** Where the visitor came from, trimmed to fit the column. */
    public static function source(Request $request): string
    {
        return mb_substr((string) $request->headers->get('referer', $request->fullUrl()), 0, 300);
    }
}
