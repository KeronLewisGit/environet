<?php

namespace App\Enums;

enum SolicitationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Pricing = 'pricing';
    case Submitted = 'submitted';
    case Won = 'won';
    case Lost = 'lost';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Bid / no-bid review',
            self::Pricing => 'Pricing',
            self::Submitted => 'Bid submitted',
            self::Won => 'Awarded',
            self::Lost => 'Not awarded',
            self::Declined => 'Declined to bid',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Won => 'good',
            self::Lost, self::Declined => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Reviewing => 'eye',
            self::Pricing => 'calculator',
            self::Submitted => 'send',
            self::Won => 'check-circle',
            self::Lost, self::Declined => 'x',
        };
    }

    /** Still being worked: the deadline matters. */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::New, self::Reviewing, self::Pricing];
    }
}
