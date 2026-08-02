<?php

namespace App\Enums;

/**
 * Damage severity of a returned/replaced unit.
 */
enum DamageType: string
{
    case PARTIAL = 'partial';
    case FULL    = 'full';

    public function label(): string
    {
        return match ($this) {
            self::PARTIAL => 'Partial Damage',
            self::FULL    => 'Full Damage',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PARTIAL => 'warning',
            self::FULL    => 'danger',
        };
    }
}
