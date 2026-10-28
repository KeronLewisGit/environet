<?php

namespace App\Enums;

/** What kind of request came in: federal solicitation types, and the requests prime contractors send. */
enum SolicitationType: string
{
    case Rfq = 'rfq';
    case Rfp = 'rfp';
    case Ifb = 'ifb';
    case SourcesSought = 'sources';
    case TaskOrder = 'task_order';
    case SubQuote = 'sub_quote';
    case Teaming = 'teaming';

    public function label(): string
    {
        return match ($this) {
            self::Rfq => 'Request for quotation (RFQ)',
            self::Rfp => 'Request for proposal (RFP)',
            self::Ifb => 'Invitation for bid (IFB)',
            self::SourcesSought => 'Sources sought / request for information',
            self::TaskOrder => 'Task order request under a MATOC, MACC or IDIQ',
            self::SubQuote => 'Subcontract quote request from a prime contractor',
            self::Teaming => 'Teaming or joint-venture proposal',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Rfq => 'RFQ',
            self::Rfp => 'RFP',
            self::Ifb => 'IFB',
            self::SourcesSought => 'Sources sought',
            self::TaskOrder => 'Task order',
            self::SubQuote => 'Sub quote',
            self::Teaming => 'Teaming',
        };
    }
}
