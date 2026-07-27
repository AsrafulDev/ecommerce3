<?php

namespace App\Services;

use App\Enums\WarrantyClaimStatus;
use App\Enums\WarrantySaleStatus;
use App\Enums\WarrantyType;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\SupplierWarranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimStage;
use App\Models\WarrantySale;
use Illuminate\Support\Facades\DB;

class WarrantyService
{
    /**
     * Generate warranty tiers for a product based on supplier warranty.
     */
    public function generateTiers(Product $product, ?SupplierWarranty $supplierWarranty = null): array
    {
        $tiers = [];
        $basePrice = $product->selling_price ?? $product->purchase_price * 1.25;

        // Tier 0: No Warranty (always available)
        $tiers[] = ProductWarrantyTier::updateOrCreate(
            ['product_id' => $product->id, 'warranty_type' => WarrantyType::NONE->value],
            [
                'tier_name'       => 'No Warranty',
                'warranty_days'   => 0,
                'price'           => round($basePrice * 0.90, 2),
                'additional_cost' => 0,
                'sort_order'      => 0,
                'is_active'       => true,
            ]
        );

        // Tier 1: Supplier Warranty (if available)
        if ($supplierWarranty && $supplierWarranty->is_sellable) {
            $tiers[] = ProductWarrantyTier::updateOrCreate(
                ['product_id' => $product->id, 'warranty_type' => WarrantyType::SUPPLIER_WARRANTY->value],
                [
                    'tier_name'       => 'Standard Warranty',
                    'warranty_days'   => $supplierWarranty->remaining_days,
                    'price'           => $basePrice,
                    'additional_cost' => 0,
                    'sort_order'      => 1,
                    'is_active'       => true,
                ]
            );
        }

        // Tier 2: Extended Store Warranty
        $existingExtended = ProductWarrantyTier::where('product_id', $product->id)
            ->where('warranty_type', WarrantyType::EXTENDED_WARRANTY->value)
            ->first();

        if ($existingExtended) {
            $tiers[] = $existingExtended;
        }

        return $tiers;
    }

    /**
     * Create warranty sale record when order is placed.
     */
    public function createWarrantySale(
        Order $order,
        OrderDetails $orderDetail,
        ProductWarrantyTier $tier
    ): WarrantySale {
        return DB::transaction(function () use ($order, $orderDetail, $tier) {
            $supplierWarranty = null;
            if ($tier->warranty_type !== WarrantyType::NONE->value) {
                $supplierWarranty = SupplierWarranty::where('product_id', $orderDetail->product_id)
                    ->where('is_transferable', true)
                    ->where('warranty_end_date', '>', now())
                    ->orderBy('warranty_end_date')
                    ->first();
            }

            return WarrantySale::updateOrCreate(
                ['order_detail_id' => $orderDetail->id],
                [
                    'order_id'                 => $order->id,
                    'product_warranty_tier_id' => $tier->id,
                    'customer_id'              => $order->customer_id,
                    'product_id'               => $orderDetail->product_id,
                    'supplier_warranty_id'     => $supplierWarranty?->id,
                    'warranty_type'            => $tier->warranty_type,
                    'warranty_days'            => $tier->warranty_days,
                    'warranty_start_date'      => null,
                    'warranty_end_date'        => null,
                    'warranty_price'           => $tier->price,
                    'status'                   => WarrantySaleStatus::ACTIVE->value,
                ]
            );
        });
    }

    /**
     * Activate warranty countdown when order is delivered.
     */
    public function activateOnDelivery(Order $order): void
    {
        $warrantySales = WarrantySale::where('order_id', $order->id)->get();

        foreach ($warrantySales as $sale) {
            if ($sale->warranty_days > 0) {
                // For supplier warranty: use the supplier's original warranty period
                if ($sale->supplier_warranty_id) {
                    $sw = \App\Models\SupplierWarranty::find($sale->supplier_warranty_id);
                    if ($sw && $sw->warranty_end_date) {
                        $sale->update([
                            'warranty_start_date' => now(),
                            'warranty_end_date'   => $sw->warranty_end_date,
                            'status'              => WarrantySaleStatus::ACTIVE->value,
                        ]);
                        continue;
                    }
                }
                // Extended warranty or fallback: calculate from today
                $sale->update([
                    'warranty_start_date' => now(),
                    'warranty_end_date'   => now()->addDays($sale->warranty_days),
                    'status'              => WarrantySaleStatus::ACTIVE->value,
                ]);
            }
        }
    }

    /**
     * File a warranty claim for a customer.
     */
    public function fileClaim(WarrantySale $warrantySale, array $data): WarrantyClaim
    {
        if (!$warrantySale->can_claim) {
            throw new \RuntimeException('This warranty is not eligible for claims.');
        }

        return DB::transaction(function () use ($warrantySale, $data) {
            $warrantySale->update(['status' => WarrantySaleStatus::CLAIMED->value]);

            $claim = WarrantyClaim::create([
                'warranty_sale_id'  => $warrantySale->id,
                'customer_id'       => $warrantySale->customer_id,
                'order_id'          => $warrantySale->order_id,
                'product_id'        => $warrantySale->product_id,
                'claim_number'      => $this->generateClaimNumber(),
                'issue_description' => $data['issue_description'],
                'issue_type'        => $data['issue_type'] ?? 'other',
                'attachments'       => $data['attachments'] ?? [],
                'status'            => WarrantyClaimStatus::SUBMITTED->value,
                'claimed_at'        => now(),
            ]);

            WarrantyClaimStage::create([
                'warranty_claim_id' => $claim->id,
                'stage'             => 'submitted',
                'status'            => 'completed',
                'started_at'        => now(),
                'completed_at'      => now(),
            ]);

            WarrantyClaimStage::create([
                'warranty_claim_id' => $claim->id,
                'stage'             => 'document_verification',
                'status'            => 'pending',
                'started_at'        => now(),
            ]);

            return $claim;
        });
    }

    /**
     * Advance a claim to the next stage.
     */
    public function advanceClaimStage(WarrantyClaim $claim, ?string $notes = null): void
    {
        $currentStage = $claim->currentStage;
        if ($currentStage) {
            $currentStage->complete($notes);
        }

        $nextStage = $this->getNextStage($claim);
        if ($nextStage) {
            WarrantyClaimStage::create([
                'warranty_claim_id' => $claim->id,
                'stage'             => $nextStage,
                'status'            => 'pending',
                'handled_by'        => auth()->id(),
                'started_at'        => now(),
            ]);
        }
    }

    /**
     * Cron: expire warranties past their end date.
     */
    public function expireWarranties(): int
    {
        return WarrantySale::where('status', WarrantySaleStatus::ACTIVE->value)
            ->where('warranty_end_date', '<', now())
            ->where('warranty_days', '>', 0)
            ->update(['status' => WarrantySaleStatus::EXPIRED->value]);
    }

    /**
     * Void warranty when order item is returned.
     */
    public function voidWarranty(WarrantySale $warrantySale): void
    {
        $warrantySale->update(['status' => WarrantySaleStatus::VOID->value]);
    }

    // ── Private ──────────────────────────────

    private function generateClaimNumber(): string
    {
        $prefix = 'WCL-';
        $date   = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -5));
        return $prefix . $date . '-' . $random;
    }

    private function getNextStage(WarrantyClaim $claim): ?string
    {
        return match ($claim->status) {
            'submitted'    => 'document_verification',
            'under_review' => 'product_inspection',
            'approved'     => 'repair',
            'in_service'   => 'quality_check',
            'serviced'     => 'ready_for_return',
            default        => null,
        };
    }
}
