<?php

namespace App\Support;

use App\Enums\Area;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Emails the office and confirms to the sender. Each back-office area can have its own inbox
 * (ENET_NOTIFY_SOLICITATIONS, _TEAMING, _CAREERS, ...); otherwise ENET_NOTIFY_EMAIL hears everything.
 * Mail goes out after the response is sent, so a slow mail server never loses a submission.
 */
final class Notify
{
    /** @return list<string> */
    public static function recipients(?Area $area = null): array
    {
        return array_values(array_filter(self::configured($area), fn (string $a) => (bool) filter_var($a, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Entries in the notify settings that are not email addresses (typos), shown on the dashboard.
     *
     * @return list<string>
     */
    public static function invalid(): array
    {
        $all = self::split((string) enet('notify.default'));
        foreach (Area::cases() as $area) {
            $all = array_merge($all, self::split((string) enet('notify.'.$area->inbox())));
        }
        $all = array_values(array_unique($all));

        return array_values(array_filter($all, fn (string $a) => ! filter_var($a, FILTER_VALIDATE_EMAIL)));
    }

    /** @return list<string> */
    private static function configured(?Area $area): array
    {
        $specific = $area ? self::split((string) enet('notify.'.$area->inbox())) : [];

        return $specific !== [] ? $specific : self::split((string) enet('notify.default'));
    }

    /** @return list<string> */
    private static function split(string $list): array
    {
        return preg_split('/[\s,;]+/', $list, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function office(BaseNotification $notification, ?Area $area = null): void
    {
        $to = self::recipients($area);

        if ($to === []) {
            Log::warning('ENET_NOTIFY_EMAIL is not set; nobody was emailed about a new submission.', ['notification' => $notification::class, 'area' => $area?->value]);

            return;
        }

        self::send($to, $notification);
    }

    /** Confirmation to the sender, when they gave a usable email address. */
    public static function sender(?string $email, BaseNotification $notification): void
    {
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        self::send([$email], $notification);
    }

    /** @param list<string> $to */
    private static function send(array $to, BaseNotification $notification): void
    {
        defer(function () use ($to, $notification): void {
            try {
                Notification::route('mail', $to)->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
