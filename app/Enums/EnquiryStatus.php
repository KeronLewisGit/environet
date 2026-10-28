<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Answered => 'Answered',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Answered => 'good',
            self::Closed => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Answered => 'check-circle',
            self::Closed => 'x',
        };
    }
}
