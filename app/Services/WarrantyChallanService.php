<?php

namespace App\Services;

use App\Enums\WarrantyClaimStatus;
use App\Models\WarrantyChallan;
use App\Models\WarrantyClaim;
use Illuminate\Support\Str;

class WarrantyChallanService
{
    /**
     * Generate challan when product is received from customer.
     */
    public function generateReceiveChallan(WarrantyClaim $claim, array $data): WarrantyChallan
    {
        $challanNo = $this->generateChallanNo('RCV');

        $challanData = [
            'challan_no'        => $challanNo,
            'challan_type'      => 'receive',
            'date'              => now()->format('Y-m-d H:i'),
            'store_name'        => config('app.name', 'Store'),
            'store_address'     => optional(\App\Models\GeneralSetting::first())->address ?? '',
            'store_phone'       => optional(\App\Models\GeneralSetting::first())->phone ?? '',
            'customer_name'     => $claim->customer->name ?? 'N/A',
            'customer_phone'    => $claim->customer->phone ?? 'N/A',
            'product_name'      => $claim->product->name ?? 'N/A',
            'serial_number'     => $this->formatSerialNumbers($claim->warrantySale->serial_numbers ?? []),
            'claim_number'      => $claim->claim_number,
            'issue_description' => $claim->issue_description,
            'received_condition'=> $data['condition'] ?? 'As described',
            'accessories'       => $data['accessories'] ?? 'None',
            'notes'             => $data['notes'] ?? '',
            'footer_text'       => 'This is a computer-generated challan. Signature not required.',
        ];

        $challan = WarrantyChallan::create([
            'warranty_claim_id' => $claim->id,
            'challan_type'      => 'receive',
            'challan_no'        => $challanNo,
            'challan_data'      => $challanData,
            'generated_by'      => auth()->id(),
        ]);

        $claim->update([
            'status'              => WarrantyClaimStatus::PRODUCT_RECEIVED->value,
            'product_received_at' => now(),
            'receive_challan_no'  => $challanNo,
            'receive_notes'       => $data['notes'] ?? null,
        ]);

        $claim->stages()->create([
            'stage'        => 'product_inspection',
            'status'       => 'completed',
            'notes'        => 'Product received from customer. Challan #' . $challanNo,
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        return $challan;
    }

    /**
     * Generate challan when product is sent to supplier.
     * IMPORTANT: No customer info on this challan — only store + supplier + product.
     */
    public function generateSendToSupplierChallan(WarrantyClaim $claim, array $data): WarrantyChallan
    {
        $challanNo = $this->generateChallanNo('SUP');
        $supplier = \App\Models\Supplier::find($data['supplier_id']);
        $setting = \App\Models\GeneralSetting::first();

        $challanData = [
            'challan_no'        => $challanNo,
            'challan_type'      => 'send_to_supplier',
            'date'              => now()->format('Y-m-d H:i'),
            'store_name'        => config('app.name', 'Store'),
            'store_address'     => optional($setting)->address ?? '',
            'store_phone'       => optional($setting)->phone ?? '',
            'store_contact'     => optional($setting)->contact_person ?? 'N/A',
            'supplier_name'     => $supplier->name ?? 'N/A',
            'supplier_address'  => $supplier->address ?? 'N/A',
            'supplier_phone'    => $supplier->phone ?? 'N/A',
            'supplier_contact'  => $supplier->contact_person ?? 'N/A',
            'product_name'      => $claim->product->name ?? 'N/A',
            'serial_number'     => $this->formatSerialNumbers($claim->warrantySale->serial_numbers ?? []),
            'claim_number'      => $claim->claim_number,
            'warranty_type'     => $claim->warrantySale->warranty_type,
            'warranty_days'     => $claim->warrantySale->warranty_days,
            'courier'           => $data['courier'] ?? 'N/A',
            'tracking_id'       => $data['tracking_id'] ?? 'N/A',
            'notes'             => $data['notes'] ?? '',
            'footer_text'       => 'For Supplier Warranty Claim. Product SN: ' . $this->formatSerialNumbers($claim->warrantySale->serial_numbers ?? []),
        ];

        $challan = WarrantyChallan::create([
            'warranty_claim_id' => $claim->id,
            'challan_type'      => 'send_to_supplier',
            'challan_no'        => $challanNo,
            'challan_data'      => $challanData,
            'generated_by'      => auth()->id(),
        ]);

        $claim->update([
            'status'              => WarrantyClaimStatus::SENT_TO_SUPPLIER->value,
            'sent_to_supplier_at' => now(),
            'supplier_challan_no' => $challanNo,
            'sent_supplier_id'    => $data['supplier_id'] ?? null,
            'supplier_send_notes' => $data['notes'] ?? null,
        ]);

        $claim->stages()->create([
            'stage'        => 'sent_to_supplier',
            'status'       => 'completed',
            'notes'        => 'Sent to supplier for warranty claim. Challan #' . $challanNo,
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        return $challan;
    }

    /**
     * Generate challan when product is returned from supplier.
     */
    public function generateSupplierReturnChallan(WarrantyClaim $claim, array $data): WarrantyChallan
    {
        $challanNo = $this->generateChallanNo('SRT');

        $challanData = [
            'challan_no'              => $challanNo,
            'challan_type'            => 'receive_return',
            'date'                    => now()->format('Y-m-d H:i'),
            'supplier_name'           => $claim->sentSupplier->name ?? 'N/A',
            'supplier_return_challan' => $data['supplier_return_challan'] ?? 'N/A',
            'store_name'              => config('app.name', 'Store'),
            'product_name'            => $claim->product->name ?? 'N/A',
            'original_sn'             => $this->formatSerialNumbers($claim->warrantySale->serial_numbers ?? []),
            'replacement_sn'          => $data['replacement_sn'] ?? null,
            'return_type'             => $data['return_type'] ?? 'repaired',
            'claim_number'            => $claim->claim_number,
            'supplier_charge'         => $data['supplier_charge'] ?? 0,
            'notes'                   => $data['notes'] ?? '',
            'footer_text'             => 'Product returned from supplier warranty claim.',
        ];

        $challan = WarrantyChallan::create([
            'warranty_claim_id' => $claim->id,
            'challan_type'      => 'receive_return',
            'challan_no'        => $challanNo,
            'challan_data'      => $challanData,
            'generated_by'      => auth()->id(),
        ]);

        $claim->update([
            'status'                     => WarrantyClaimStatus::SUPPLIER_RETURNED->value,
            'returned_from_supplier_at'  => now(),
            'supplier_return_challan_no' => $data['supplier_return_challan'] ?? null,
            'replacement_sn'             => $data['replacement_sn'] ?? null,
            'return_type'                => $data['return_type'] ?? 'repaired',
            'supplier_return_notes'      => $data['notes'] ?? null,
            'supplier_charge'            => $data['supplier_charge'] ?? null,
        ]);

        // If replaced, update the WarrantySale serial numbers
        if (($data['return_type'] ?? '') === 'replaced' && !empty($data['replacement_sn'])) {
            $oldSn = $this->formatSerialNumbers($claim->warrantySale->serial_numbers ?? []);
            $claim->warrantySale->update(['serial_numbers' => [$data['replacement_sn']]]);
            $claim->notes()->create([
                'user_id' => auth()->id(),
                'note'    => "Serial Number updated: {$oldSn} → {$data['replacement_sn']} (replaced by supplier)",
            ]);
        }

        $claim->stages()->create([
            'stage'        => 'supplier_return',
            'status'       => 'completed',
            'notes'        => 'Product returned from supplier. Challan #' . $challanNo,
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        return $challan;
    }

    /**
     * Generate delivery challan when product is returned to customer.
     */
    public function generateDeliveryChallan(WarrantyClaim $claim, array $data): WarrantyChallan
    {
        $challanNo = $this->generateChallanNo('DLV');
        $warrantySale = $claim->warrantySale;

        $challanData = [
            'challan_no'       => $challanNo,
            'challan_type'     => 'delivery',
            'date'             => now()->format('Y-m-d H:i'),
            'store_name'       => config('app.name', 'Store'),
            'store_address'    => optional(\App\Models\GeneralSetting::first())->address ?? '',
            'store_phone'      => optional(\App\Models\GeneralSetting::first())->phone ?? '',
            'customer_name'    => $claim->customer->name ?? 'N/A',
            'customer_phone'   => $claim->customer->phone ?? 'N/A',
            'customer_address' => $claim->order->shipping->address ?? 'N/A',
            'product_name'     => $claim->product->name ?? 'N/A',
            'serial_number'    => $this->formatSerialNumbers($warrantySale->serial_numbers ?? []),
            'claim_number'     => $claim->claim_number,
            'return_type'      => $claim->return_type ?? 'repaired',
            'warranty_type'    => $warrantySale->warranty_type,
            'warranty_days'    => $warrantySale->warranty_days,
            'warranty_start'   => $warrantySale->warranty_start_date?->format('Y-m-d'),
            'warranty_end'     => $warrantySale->warranty_end_date?->format('Y-m-d'),
            'claim_count'      => \App\Models\WarrantyClaim::where('warranty_sale_id', $warrantySale->id)->count(),
            'delivery_method'  => $data['delivery_method'] ?? 'Counter Pickup',
            'notes'            => $data['notes'] ?? '',
            'footer_text'      => 'Thank you for your patience. Product delivered under warranty claim #' . $claim->claim_number,
        ];

        $challan = WarrantyChallan::create([
            'warranty_claim_id' => $claim->id,
            'challan_type'      => 'delivery',
            'challan_no'        => $challanNo,
            'challan_data'      => $challanData,
            'generated_by'      => auth()->id(),
        ]);

        $claim->update([
            'status'                   => WarrantyClaimStatus::DELIVERED->value,
            'ready_for_delivery_at'    => $claim->ready_for_delivery_at ?? now(),
            'delivery_challan_no'      => $challanNo,
            'delivered_to_customer_at' => now(),
            'delivery_notes'           => $data['notes'] ?? null,
        ]);

        $claim->transitionTo(WarrantyClaimStatus::RESOLVED, 'Delivered to customer. Challan #' . $challanNo);

        $claim->stages()->create([
            'stage'        => 'returned_to_customer',
            'status'       => 'completed',
            'notes'        => 'Delivered to customer. Challan #' . $challanNo,
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        return $challan;
    }

    // ── Helper ──────────────────────────────

    private function generateChallanNo(string $prefix): string
    {
        return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));
    }

    private function formatSerialNumbers(?array $serialNumbers): string
    {
        if (empty($serialNumbers)) {
            return 'N/A';
        }
        return implode(', ', array_filter($serialNumbers));
    }
}
