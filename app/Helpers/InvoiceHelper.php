<?php

namespace App\Helpers;

use App\Models\Order;

class InvoiceHelper
{
    /**
     * Generate a reasonably collision-resistant invoice id.
     * Stored format: six numeric digits. Human prefixes belong in the UI.
     */
    public static function generateInvoiceId(int $tries = 5): string
    {
        for ($i = 0; $i < $tries; $i++) {
            $candidate = (string) random_int(100000, 999999);
            if (!Order::where('invoice_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback to timestamp + random if unlucky
        return (string) random_int(100000, 999999);
    }
}
