<?php

namespace App\Enums;

enum JobCategory: string
{
    case Uxo = 'uxo';
    case Scientist = 'scientist';
    case Trades = 'trades';
    case Construction = 'construction';
    case Safety = 'safety';
    case Office = 'office';

    public function label(): string
    {
        return match ($this) {
            self::Uxo => 'UXO technician or MEC field team',
            self::Scientist => 'Environmental scientist, geologist or engineer',
            self::Trades => 'Construction trades and labor',
            self::Construction => 'Construction management, QC or project engineering',
            self::Safety => 'Site safety and health',
            self::Office => 'Office, accounting and administration',
        };
    }

    /** A résumé matters for professional and office roles; field trades can apply without one. */
    public function needsCv(): bool
    {
        return in_array($this, [self::Scientist, self::Construction, self::Office], true);
    }

    public function isField(): bool
    {
        return in_array($this, [self::Uxo, self::Trades, self::Safety], true);
    }
}
