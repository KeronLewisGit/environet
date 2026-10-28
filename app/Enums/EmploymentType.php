<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case Project = 'project';
    case Temporary = 'temporary';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full time',
            self::Project => 'For the length of a project',
            self::Temporary => 'Temporary',
        };
    }

    /** schema.org JobPosting employmentType */
    public function schema(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::Project => 'CONTRACTOR',
            self::Temporary => 'TEMPORARY',
        };
    }
}
