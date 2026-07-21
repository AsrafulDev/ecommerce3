<?php

namespace App\Console\Commands;

use App\Models\ProductWarrantyTier;
use App\Models\SupplierWarranty;
use Illuminate\Console\Command;

class UpdateWarrantyTiers extends Command
{
    protected $signature = 'warranty:update-tiers';
    protected $description = 'Update product warranty tiers based on remaining supplier warranty days';

    public function handle(): int
    {
        $updated = 0;

        // Get all supplier warranty tiers
        $tiers = ProductWarrantyTier::where('warranty_type', 'supplier_warranty')->get();

        foreach ($tiers as $tier) {
            $supplierWarranty = SupplierWarranty::where('product_id', $tier->product_id)
                ->where('is_transferable', true)
                ->where('warranty_end_date', '>', now())
                ->orderBy('warranty_end_date')
                ->first();

            if ($supplierWarranty) {
                $remainingDays = $supplierWarranty->remaining_days;

                if ($tier->warranty_days !== $remainingDays) {
                    $tier->update([
                        'warranty_days' => $remainingDays,
                        'tier_name'     => "{$remainingDays} Days Warranty",
                    ]);
                    $updated++;
                }
            } else {
                // No valid supplier warranty — deactivate tier
                if ($tier->is_active) {
                    $tier->update(['is_active' => false]);
                    $updated++;
                }
            }
        }

        $this->info("Updated {$updated} warranty tiers.");
        return self::SUCCESS;
    }
}
