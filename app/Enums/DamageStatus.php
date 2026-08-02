<?php

namespace App\Enums;

/**
 * Damage product lifecycle status.
 */
enum DamageStatus: string
{
    case ON_WARRANTY   = 'on_warranty';    // awaiting supplier / being processed
    case SUPPLIER_HOLD = 'supplier_hold';  // at supplier
    case IN_SERVICE    = 'in_service';     // being repaired in-house
    case RESELLABLE    = 'resellable';     // repaired → back to sellable stock
    case UNSELLABLE    = 'unsellable';     // damaged beyond repair → write-off
    case DISCARDED     = 'discarded';      // disposed

    public function label(): string
    {
        return match ($this) {
            self::ON_WARRANTY   => 'On Warranty',
            self::SUPPLIER_HOLD => 'Supplier Hold',
            self::IN_SERVICE    => 'In Service',
            self::RESELLABLE    => 'Resellable',
            self::UNSELLABLE    => 'Unsellable',
            self::DISCARDED     => 'Discarded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ON_WARRANTY   => 'warning',
            self::SUPPLIER_HOLD => 'purple',
            self::IN_SERVICE    => 'orange',
            self::RESELLABLE    => 'success',
            self::UNSELLABLE    => 'danger',
            self::DISCARDED     => 'dark',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::RESELLABLE, self::UNSELLABLE, self::DISCARDED]);
    }
}
