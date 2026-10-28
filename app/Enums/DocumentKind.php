<?php

namespace App\Enums;

/** Certificates and insurance a teaming partner, subcontractor or supplier files with us. */
enum DocumentKind: string
{
    case Liability = 'liability';
    case WorkersComp = 'workers_comp';
    case License = 'license';
    case SbaCert = 'sba_cert';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Liability => 'Certificate of insurance: general liability',
            self::WorkersComp => 'Workers\' compensation insurance',
            self::License => 'Contractor license',
            self::SbaCert => 'SBA certification letter',
            self::Other => 'Capability statement or other document',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Liability => 'General liability',
            self::WorkersComp => 'Workers\' comp',
            self::License => 'Contractor license',
            self::SbaCert => 'SBA certification',
            self::Other => 'Other',
        };
    }

    public function numberLabel(): string
    {
        return match ($this) {
            self::Liability, self::WorkersComp => 'Insurer and policy number',
            self::License => 'License number and issuing state or territory',
            self::SbaCert => 'Program (e.g. HUBZone, 8(a), WOSB)',
            self::Other => 'Description',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Liability => 'An ACORD certificate or cover note showing the insurer, policy number, limits and expiration date.',
            self::WorkersComp => 'Proof of workers\' compensation coverage (and Hawaiʻi TDI where it applies), with its expiration date.',
            self::License => 'Your current contractor license, for example from the Hawaiʻi DCCA Contractors License Board or the Guam Contractors License Board.',
            self::SbaCert => 'Only if you hold one. Your self-declared status below is checked against SAM and the SBA before we rely on it.',
            self::Other => 'For example your capability statement, a W-9 or a SAM registration printout.',
        };
    }

    public function expires(): bool
    {
        return $this !== self::Other;
    }

    /** @return list<self> */
    public static function certificates(): array
    {
        return [self::Liability, self::WorkersComp, self::License, self::SbaCert];
    }
}
