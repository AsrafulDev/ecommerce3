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
    case IN_SERVICE   = 'in_service';
    case SERVICED     = 'serviced';
    case RESOLVED     = 'resolved';
    case REJECTED     = 'rejected';
    case CANCELLED    = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED    => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED     => 'Approved',
            self::IN_SERVICE   => 'In Service',
            self::SERVICED     => 'Serviced',
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
            self::IN_SERVICE   => 'orange',
            self::SERVICED     => 'teal',
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
            self::APPROVED     => [self::IN_SERVICE, self::CANCELLED],
            self::IN_SERVICE   => [self::SERVICED, self::REJECTED],
            self::SERVICED     => [self::RESOLVED],
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
