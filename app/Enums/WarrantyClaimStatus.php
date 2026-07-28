<?php

namespace App\Enums;

/**
 * Warranty claim lifecycle status.
 */
enum WarrantyClaimStatus: string
{
    case SUBMITTED    = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED     = 'approved';
    case AWAITING_PRODUCT = 'awaiting_product';
    case PRODUCT_RECEIVED = 'product_received';
    case IN_SERVICE   = 'in_service';
    case SENT_TO_SUPPLIER = 'sent_to_supplier';
    case AWAITING_SUPPLIER_RETURN = 'awaiting_supplier_return';
    case SUPPLIER_RETURNED = 'supplier_returned';
    case SERVICED     = 'serviced';
    case READY_FOR_DELIVERY = 'ready_for_delivery';
    case DELIVERED    = 'delivered';
    case RESOLVED     = 'resolved';
    case REJECTED     = 'rejected';
    case CANCELLED    = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED    => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED     => 'Approved',
            self::AWAITING_PRODUCT => 'Awaiting Product',
            self::PRODUCT_RECEIVED => 'Product Received',
            self::IN_SERVICE   => 'In Service',
            self::SENT_TO_SUPPLIER => 'Sent to Supplier',
            self::AWAITING_SUPPLIER_RETURN => 'Awaiting Supplier',
            self::SUPPLIER_RETURNED => 'Supplier Returned',
            self::SERVICED     => 'Serviced',
            self::READY_FOR_DELIVERY => 'Ready for Delivery',
            self::DELIVERED    => 'Delivered',
            self::RESOLVED     => 'Resolved',
            self::REJECTED     => 'Rejected',
            self::CANCELLED    => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SUBMITTED    => 'warning',
            self::UNDER_REVIEW => 'info',
            self::APPROVED     => 'primary',
            self::AWAITING_PRODUCT => 'secondary',
            self::PRODUCT_RECEIVED => 'cyan',
            self::IN_SERVICE   => 'orange',
            self::SENT_TO_SUPPLIER => 'purple',
            self::AWAITING_SUPPLIER_RETURN => 'pink',
            self::SUPPLIER_RETURNED => 'indigo',
            self::SERVICED     => 'teal',
            self::READY_FOR_DELIVERY => 'lime',
            self::DELIVERED    => 'success',
            self::RESOLVED     => 'success',
            self::REJECTED     => 'danger',
            self::CANCELLED    => 'dark',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::RESOLVED, self::REJECTED, self::CANCELLED]);
    }

    public function isActive(): bool
    {
        return !$this->isTerminal();
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SUBMITTED    => [self::UNDER_REVIEW, self::CANCELLED],
            self::UNDER_REVIEW => [self::APPROVED, self::REJECTED],
            self::APPROVED     => [self::AWAITING_PRODUCT, self::IN_SERVICE, self::CANCELLED],
            self::AWAITING_PRODUCT => [self::PRODUCT_RECEIVED, self::CANCELLED],
            self::PRODUCT_RECEIVED => [self::SENT_TO_SUPPLIER, self::IN_SERVICE, self::CANCELLED],
            self::IN_SERVICE   => [self::SERVICED, self::REJECTED],
            self::SENT_TO_SUPPLIER => [self::AWAITING_SUPPLIER_RETURN, self::CANCELLED],
            self::AWAITING_SUPPLIER_RETURN => [self::SUPPLIER_RETURNED],
            self::SUPPLIER_RETURNED => [self::SERVICED, self::IN_SERVICE],
            self::SERVICED     => [self::READY_FOR_DELIVERY],
            self::READY_FOR_DELIVERY => [self::DELIVERED],
            self::DELIVERED    => [self::RESOLVED],
            self::RESOLVED     => [],
            self::REJECTED     => [],
            self::CANCELLED    => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
