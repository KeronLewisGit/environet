<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Interview = 'interview';
    case Hired = 'hired';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Reviewing',
            self::Interview => 'Interview',
            self::Hired => 'Hired',
            self::Declined => 'Not this time',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Hired => 'good',
            self::Declined => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Reviewing => 'eye',
            self::Interview => 'calendar',
            self::Hired => 'check-circle',
            self::Declined => 'x',
        };
    }

    /** @return list<self> applications still being considered. */
    public static function active(): array
    {
        return [self::New, self::Reviewing, self::Interview, self::Hired];
    }
}
