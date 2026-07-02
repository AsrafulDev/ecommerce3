<?php

namespace App\Enums;

/**
 * System-driven Order Status Enum.
 * 
 * This replaces the old DB-driven order_statuses table.
 * The system automatically determines valid transitions — 
 * admins perform ACTIONS, not manual status changes.
 */
enum OrderStatus: string
{
    // ── Lifecycle ──────────────────────────────────────────
    case PENDING           = 'pending';
    case CONFIRMED         = 'confirmed';
    case PICKING           = 'picking';
    case PACKING           = 'packing';
    case PACKED            = 'packed';
    case SHIPPED           = 'shipped';
    case OUT_FOR_DELIVERY  = 'out_for_delivery';
    case DELIVERED         = 'delivered';
    case COMPLETED         = 'completed';

    // ── Return / Post-delivery ─────────────────────────────
    case RETURN_REQUESTED  = 'return_requested';
    case RETURN_APPROVED   = 'return_approved';
    case RETURNED          = 'returned';

    // ── Terminal ───────────────────────────────────────────
    case CANCELLED         = 'cancelled';
    case CLOSED            = 'closed';

    // ═══════════════════════════════════════════════════════
    // 🏷️  Labels
    // ═══════════════════════════════════════════════════════

    public function label(): string
    {
        return match ($this) {
            self::PENDING          => 'Pending',
            self::CONFIRMED        => 'Confirmed',
            self::PICKING          => 'Picking',
            self::PACKING          => 'Packing',
            self::PACKED           => 'Packed',
            self::SHIPPED          => 'Shipped',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED        => 'Delivered',
            self::COMPLETED        => 'Completed',
            self::RETURN_REQUESTED => 'Return Requested',
            self::RETURN_APPROVED  => 'Return Approved',
            self::RETURNED         => 'Returned',
            self::CANCELLED        => 'Cancelled',
            self::CLOSED           => 'Closed',
        };
    }

    // ═══════════════════════════════════════════════════════
    // 🔀  Allowed Transitions
    // ═══════════════════════════════════════════════════════

    /**
     * Returns the list of statuses this status CAN transition to.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [
                self::CONFIRMED,
                self::CANCELLED,
            ],
            self::CONFIRMED => [
                self::PICKING,
                self::CANCELLED,
            ],
            self::PICKING => [
                self::PACKING,
                self::CANCELLED,
            ],
            self::PACKING => [
                self::PACKED,
                self::CANCELLED,
            ],
            self::PACKED => [
                self::SHIPPED,
                self::CANCELLED,
            ],
            self::SHIPPED => [
                self::OUT_FOR_DELIVERY,
                // NOTE: Cancellation after ship is NOT allowed
            ],
            self::OUT_FOR_DELIVERY => [
                self::DELIVERED,
            ],
            self::DELIVERED => [
                self::COMPLETED,
                self::RETURN_REQUESTED,
            ],
            self::COMPLETED => [
                // Terminal — can only go to return if within window
                self::RETURN_REQUESTED,
            ],
            self::RETURN_REQUESTED => [
                self::RETURN_APPROVED,
                self::CLOSED,       // rejected return
            ],
            self::RETURN_APPROVED => [
                self::RETURNED,
            ],
            self::RETURNED => [
                self::CLOSED,
            ],
            self::CANCELLED => [
                // Terminal
            ],
            self::CLOSED => [
                // Terminal
            ],
        };
    }

    /**
     * Can this status transition to $target?
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    // ═══════════════════════════════════════════════════════
    // 🎨  Badge Colors (Bootstrap)
    // ═══════════════════════════════════════════════════════

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING          => 'warning',
            self::CONFIRMED        => 'info',
            self::PICKING,
            self::PACKING,
            self::PACKED           => 'primary',
            self::SHIPPED,
            self::OUT_FOR_DELIVERY => 'info',
            self::DELIVERED        => 'success',
            self::COMPLETED        => 'success',
            self::RETURN_REQUESTED => 'warning',
            self::RETURN_APPROVED  => 'info',
            self::RETURNED         => 'secondary',
            self::CANCELLED        => 'danger',
            self::CLOSED           => 'dark',
        };
    }

    // ═══════════════════════════════════════════════════════
    // 🏭  Active statuses (consume stock)
    // ═══════════════════════════════════════════════════════

    public function consumesStock(): bool
    {
        return in_array($this, [
            self::CONFIRMED,
            self::PICKING,
            self::PACKING,
            self::PACKED,
            self::SHIPPED,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
            self::COMPLETED,
        ], true);
    }

    /**
     * Is this a terminal status? (no further transitions)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::CLOSED,
        ], true);
    }

    /**
     * Can the order still be cancelled from this status?
     */
    public function isCancellable(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::CONFIRMED,
            self::PICKING,
            self::PACKING,
            self::PACKED,
        ], true);
    }

    // ═══════════════════════════════════════════════════════
    // 🧭  Legacy: Map old numeric IDs → new enum
    // ═══════════════════════════════════════════════════════

    /**
     * Map legacy numeric order_status IDs to enum values.
     * Used during migration/transition period.
     */
    public static function fromLegacyId(int $id): self
    {
        return match ($id) {
            1  => self::PENDING,
            2  => self::CONFIRMED,
            3  => self::PICKING,
            4  => self::PACKING,
            5  => self::PACKED,       // was "Ready to Ship"
            6  => self::COMPLETED,     // was "Completed/Delivered"
            7  => self::SHIPPED,
            8  => self::OUT_FOR_DELIVERY,
            9  => self::DELIVERED,
            10 => self::RETURN_REQUESTED,
            11 => self::CANCELLED,
            12 => self::RETURN_APPROVED,
            13 => self::RETURNED,
            14 => self::CLOSED,
            default => self::PENDING,
        };
    }

    /**
     * All statuses as array for dropdowns/seeding.
     */
    public static function options(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
