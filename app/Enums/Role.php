<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Bd = 'bd';
    case Procurement = 'procurement';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Bd => 'Business development',
            self::Procurement => 'Procurement and contracts',
            self::Hr => 'Human resources',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts.',
            self::Bd => 'RFQ and bid inquiries, teaming partners, website inquiries and document rooms.',
            self::Procurement => 'Subcontractor, supplier and teaming registrations, their certificates and insurance, and document rooms.',
            self::Hr => 'Job postings, applications, résumés and applicant certifications.',
        };
    }

    /**
     * Back-office areas this role works in.
     *
     * @return list<Area>
     */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => Area::cases(),
            self::Bd => [Area::Solicitations, Area::Teaming, Area::Enquiries, Area::Rooms],
            self::Procurement => [Area::Teaming, Area::Rooms],
            self::Hr => [Area::Careers],
        };
    }
}
