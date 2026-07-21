<?php

namespace App\Enums;

enum WarrantySaleStatus: string
{
    case ACTIVE  = 'active';
    case EXPIRED = 'expired';
    case CLAIMED = 'claimed';
    case VOID    = 'void';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE  => 'Active',
            self::EXPIRED => 'Expired',
            self::CLAIMED => 'Claimed',
            self::VOID    => 'Void',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE  => 'success',
            self::EXPIRED => 'secondary',
            self::CLAIMED => 'warning',
            self::VOID    => 'dark',
        };
    }

    public function canClaim(): bool
    {
        return $this === self::ACTIVE;
    }
}
