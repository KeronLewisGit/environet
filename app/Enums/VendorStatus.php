<?php

namespace App\Enums;

enum VendorStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Under review',
            self::Approved => 'Approved vendor',
            self::Declined => 'Not approved',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Approved => 'good',
            self::Declined => 'muted',
            self::Reviewing => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Reviewing => 'eye',
            self::Approved => 'shield',
            self::Declined => 'x',
        };
    }
}
