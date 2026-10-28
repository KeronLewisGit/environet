<?php

namespace App\Enums;

enum Experience: string
{
    case None = 'none';
    case UnderTwo = 'lt2';
    case TwoToFive = '2to5';
    case FiveToTen = '5to10';
    case OverTen = 'gt10';

    public function label(): string
    {
        return match ($this) {
            self::None => 'New to the field',
            self::UnderTwo => 'Under 2 years',
            self::TwoToFive => '2 to 5 years',
            self::FiveToTen => '5 to 10 years',
            self::OverTen => 'Over 10 years',
        };
    }
}
