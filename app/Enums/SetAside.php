<?php

namespace App\Enums;

/**
 * Small-business programs. On a solicitation: the set-aside the buyer published. On a vendor:
 * what the company declares about itself (never verified by this form; staff check SAM / SBA).
 */
enum SetAside: string
{
    case SmallBusiness = 'sb';
    case Sdb = 'sdb';
    case EightA = '8a';
    case Hubzone = 'hubzone';
    case Wosb = 'wosb';
    case Edwosb = 'edwosb';
    case Vosb = 'vosb';
    case Sdvosb = 'sdvosb';
    case Unrestricted = 'none';

    public function label(): string
    {
        return match ($this) {
            self::SmallBusiness => 'Small business',
            self::Sdb => 'Small disadvantaged business (SDB)',
            self::EightA => '8(a) business development program',
            self::Hubzone => 'HUBZone',
            self::Wosb => 'Women-owned small business (WOSB)',
            self::Edwosb => 'Economically disadvantaged WOSB (EDWOSB)',
            self::Vosb => 'Veteran-owned small business (VOSB)',
            self::Sdvosb => 'Service-disabled veteran-owned small business (SDVOSB)',
            self::Unrestricted => 'Unrestricted / full and open',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::SmallBusiness => 'SB',
            self::Sdb => 'SDB',
            self::EightA => '8(a)',
            self::Hubzone => 'HUBZone',
            self::Wosb => 'WOSB',
            self::Edwosb => 'EDWOSB',
            self::Vosb => 'VOSB',
            self::Sdvosb => 'SDVOSB',
            self::Unrestricted => 'Unrestricted',
        };
    }

    /** @return list<self> the statuses a company can declare (everything except "unrestricted"). */
    public static function declarable(): array
    {
        return array_filter(self::cases(), fn (self $s) => $s !== self::Unrestricted); // Unrestricted is the last case, so the keys stay a list
    }
}
