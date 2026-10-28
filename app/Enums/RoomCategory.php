<?php

namespace App\Enums;

enum RoomCategory: string
{
    case Solicitation = 'solicitation';
    case Drawings = 'drawings';
    case Amendments = 'amendments';
    case Questions = 'questions';
    case Reports = 'reports';
    case Photos = 'photos';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Solicitation => 'Solicitation and scope of work',
            self::Drawings => 'Drawings and specifications',
            self::Amendments => 'Amendments',
            self::Questions => 'Site visit notes and Q&A',
            self::Reports => 'Reports and deliverables',
            self::Photos => 'Site photographs',
            self::Other => 'Other',
        };
    }
}
