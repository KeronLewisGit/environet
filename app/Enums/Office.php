<?php

namespace App\Enums;

/** Where an applicant wants to work. Matches the offices in config/environet.php. */
enum Office: string
{
    case Oahu = 'oahu';
    case HawaiiIsland = 'hawaii';
    case Guam = 'guam';
    case Colorado = 'colorado';
    case Any = 'any';

    public function label(): string
    {
        return match ($this) {
            self::Oahu => 'Oʻahu (Honolulu)',
            self::HawaiiIsland => 'Hawaiʻi Island (Kamuela)',
            self::Guam => 'Guam',
            self::Colorado => 'Colorado',
            self::Any => 'Anywhere, including travel to project sites',
        };
    }
}
