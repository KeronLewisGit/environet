<?php

namespace App\Enums;

/** A part of the back office. Role::areas() says who works where; policies and the nav read it. */
enum Area: string
{
    case Solicitations = 'solicitations';
    case Teaming = 'teaming';
    case Careers = 'careers';
    case Enquiries = 'enquiries';
    case Rooms = 'rooms';

    public function label(): string
    {
        return match ($this) {
            self::Solicitations => 'RFQs and bid inquiries',
            self::Teaming => 'Teaming and vendors',
            self::Careers => 'Careers',
            self::Enquiries => 'Inquiries',
            self::Rooms => 'Document rooms',
        };
    }

    /** Which ENET_NOTIFY_* inbox hears about new submissions in this area. */
    public function inbox(): string
    {
        return $this->value;
    }
}
