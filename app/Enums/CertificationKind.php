<?php

namespace App\Enums;

/**
 * Field certifications an applicant declares. Self-declared: HR checks the card or certificate
 * before anyone is placed on a site. Each carries an expiry date where one applies.
 */
enum CertificationKind: string
{
    case Hazwoper40 = 'hazwoper40';
    case Hazwoper8 = 'hazwoper8';
    case HazwoperSup = 'hazwoper_sup';
    case UxoSweep = 'uxo_sweep';
    case UxoTech1 = 'uxo1';
    case UxoTech2 = 'uxo2';
    case UxoTech3 = 'uxo3';
    case Osha10 = 'osha10';
    case Osha30 = 'osha30';
    case Em385 = 'em385';
    case Cqm = 'cqm';
    case FirstAid = 'first_aid';
    case Cdl = 'cdl';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hazwoper40 => 'HAZWOPER 40-hour (29 CFR 1910.120)',
            self::Hazwoper8 => 'HAZWOPER 8-hour annual refresher',
            self::HazwoperSup => 'HAZWOPER 8-hour supervisor',
            self::UxoSweep => 'UXO sweep personnel',
            self::UxoTech1 => 'UXO Technician I',
            self::UxoTech2 => 'UXO Technician II',
            self::UxoTech3 => 'UXO Technician III',
            self::Osha10 => 'OSHA 10-hour construction',
            self::Osha30 => 'OSHA 30-hour construction',
            self::Em385 => 'USACE EM 385-1-1 training',
            self::Cqm => 'USACE Construction Quality Management (CQM) for Contractors',
            self::FirstAid => 'First aid / CPR',
            self::Cdl => 'Commercial driver\'s license (CDL)',
            self::Other => 'Other',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Hazwoper40 => 'HAZWOPER 40',
            self::Hazwoper8 => 'HAZWOPER 8-hr refresher',
            self::HazwoperSup => 'HAZWOPER supervisor',
            self::UxoSweep => 'UXO sweep',
            self::UxoTech1 => 'UXO Tech I',
            self::UxoTech2 => 'UXO Tech II',
            self::UxoTech3 => 'UXO Tech III',
            self::Osha10 => 'OSHA 10',
            self::Osha30 => 'OSHA 30',
            self::Em385 => 'EM 385-1-1',
            self::Cqm => 'CQM',
            self::FirstAid => 'First aid / CPR',
            self::Cdl => 'CDL',
            self::Other => 'Other',
        };
    }

    /** An expiry date is required to list these: they lapse and must be current on site. */
    public function needsExpiry(): bool
    {
        return in_array($this, [self::Hazwoper40, self::Hazwoper8, self::HazwoperSup, self::UxoTech1, self::UxoTech2, self::UxoTech3, self::Cqm, self::FirstAid, self::Cdl], true);
    }

    public function expiryHint(): string
    {
        return match ($this) {
            self::Hazwoper40, self::Hazwoper8, self::HazwoperSup => 'The date your next annual refresher is due.',
            self::UxoSweep, self::UxoTech1, self::UxoTech2, self::UxoTech3 => 'The date your qualification or the HAZWOPER it depends on lapses.',
            default => 'As printed on the card or certificate.',
        };
    }
}
