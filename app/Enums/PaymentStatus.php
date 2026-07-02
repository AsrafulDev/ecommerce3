<?php

namespace App\Enums;

/**
 * Payment Status Enum.
 * 
 * Independent from order lifecycle — payment can be pending
 * while order is being fulfilled (COD), or paid upfront (prepaid).
 */
enum PaymentStatus: string
{
    case PENDING            = 'pending';
    case PAID               = 'paid';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED           = 'refunded';
    case FAILED             = 'failed';
    case CANCELLED          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING            => 'Pending',
            self::PAID               => 'Paid',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
            self::REFUNDED           => 'Refunded',
            self::FAILED             => 'Failed',
            self::CANCELLED          => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING            => 'warning',
            self::PAID               => 'success',
            self::PARTIALLY_REFUNDED => 'info',
            self::REFUNDED           => 'secondary',
            self::FAILED             => 'danger',
            self::CANCELLED          => 'dark',
        };
    }

    /**
     * Is this a "paid" state (money received)?
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::PAID,
            self::PARTIALLY_REFUNDED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Allowed transitions for payment status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [
                self::PAID,
                self::FAILED,
                self::CANCELLED,
            ],
            self::PAID => [
                self::PARTIALLY_REFUNDED,
                self::REFUNDED,
            ],
            self::PARTIALLY_REFUNDED => [
                self::REFUNDED,
            ],
            self::FAILED => [
                self::PENDING,  // retry
                self::CANCELLED,
            ],
            self::CANCELLED => [],
            self::REFUNDED  => [],
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
