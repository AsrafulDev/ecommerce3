<?php

namespace App\Enums;

enum WarrantyStageType: string
{
    case SUBMITTED        = 'submitted';
    case DOCUMENT_VERIFY  = 'document_verification';
    case PRODUCT_INSPECT  = 'product_inspection';
    case REPAIR           = 'repair';
    case REPLACEMENT      = 'replacement';
    case QUALITY_CHECK    = 'quality_check';
    case READY_FOR_RETURN = 'ready_for_return';
    case RETURNED         = 'returned_to_customer';
    case CLOSED           = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED        => 'Claim Submitted',
            self::DOCUMENT_VERIFY  => 'Document Verification',
            self::PRODUCT_INSPECT  => 'Product Inspection',
            self::REPAIR           => 'Repair / Service',
            self::REPLACEMENT      => 'Replacement',
            self::QUALITY_CHECK    => 'Quality Check',
            self::READY_FOR_RETURN => 'Ready for Return',
            self::RETURNED         => 'Returned to Customer',
            self::CLOSED           => 'Closed',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SUBMITTED        => 'fa-paper-plane',
            self::DOCUMENT_VERIFY  => 'fa-file-alt',
            self::PRODUCT_INSPECT  => 'fa-search',
            self::REPAIR           => 'fa-tools',
            self::REPLACEMENT      => 'fa-exchange-alt',
            self::QUALITY_CHECK    => 'fa-clipboard-check',
            self::READY_FOR_RETURN => 'fa-box',
            self::RETURNED         => 'fa-hand-holding',
            self::CLOSED           => 'fa-check-circle',
        };
    }
}
