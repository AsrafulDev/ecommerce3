<?php

namespace App\Enums;

/**
 * Warranty source/type classification.
 */
enum WarrantyType: string
{
    case NONE              = 'none';
    case SUPPLIER_WARRANTY = 'supplier_warranty';
    case STORE_WARRANTY    = 'store_warranty';
    case EXTENDED_WARRANTY = 'extended_warranty';

    public function label(): string
    {
        return match ($this) {
            self::NONE              => 'No Warranty',
            self::SUPPLIER_WARRANTY => 'Supplier Warranty',
            self::STORE_WARRANTY    => 'Store Warranty',
            self::EXTENDED_WARRANTY => 'Extended Warranty',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NONE              => 'secondary',
            self::SUPPLIER_WARRANTY => 'info',
            self::STORE_WARRANTY    => 'primary',
            self::EXTENDED_WARRANTY => 'success',
        };
    }

    public function hasCoverage(): bool
    {
        return $this !== self::NONE;
    }

    public function isStoreLiable(): bool
    {
        return in_array($this, [self::STORE_WARRANTY, self::EXTENDED_WARRANTY]);
    }
}
