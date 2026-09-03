<?php

namespace App\Helpers;

use App\Models\Order;
use Illuminate\Support\Str;

class InvoiceHelper
{
    /**
     * Generate a reasonably collision-resistant invoice id.
     * Format: INV-YYMMDD-XXXXX
     */
    public static function generateInvoiceId(int $tries = 5): string
    {
        for ($i = 0; $i < $tries; $i++) {
            $candidate = 'INV-' . date('ymd') . '-' . strtoupper(Str::random(5));
            if (!Order::where('invoice_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback to timestamp + random if unlucky
        return 'INV-' . time() . '-' . strtoupper(Str::random(4));
    }
}
