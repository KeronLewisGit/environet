<?php

namespace App\Enums;

enum VendorType: string
{
    case Teaming = 'teaming';
    case Subcontractor = 'subcontractor';
    case Supplier = 'supplier';
    case Equipment = 'equipment';
    case Professional = 'professional';

    public function label(): string
    {
        return match ($this) {
            self::Teaming => 'Teaming partner (prime or sub on a federal bid)',
            self::Subcontractor => 'Subcontractor',
            self::Supplier => 'Materials or equipment supplier',
            self::Equipment => 'Equipment rental',
            self::Professional => 'Professional services (design, laboratory, surveying)',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Teaming => 'Teaming partner',
            self::Subcontractor => 'Subcontractor',
            self::Supplier => 'Supplier',
            self::Equipment => 'Equipment rental',
            self::Professional => 'Professional services',
        };
    }

    /** Teaming partners and subcontractors work on federal jobs, so they need a SAM Unique Entity ID. */
    public function needsUei(): bool
    {
        return in_array($this, [self::Teaming, self::Subcontractor], true);
    }

    /** @return list<DocumentKind> */
    public function requiredDocuments(): array
    {
        return match ($this) {
            self::Subcontractor => [DocumentKind::Liability, DocumentKind::WorkersComp, DocumentKind::License],
            self::Teaming, self::Professional => [DocumentKind::Liability],
            self::Supplier, self::Equipment => [DocumentKind::Liability],
        };
    }
}
