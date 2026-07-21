<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use App\Services\WarrantyDisplayService;
use App\Services\WarrantyPriceCalculator;
use App\Services\WarrantyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarrantyApiController extends Controller
{
    public function __construct(
        private WarrantyDisplayService $displayService,
        private WarrantyPriceCalculator $priceCalculator,
        private WarrantyService $warrantyService,
    ) {}

    // ── Public ────────────────────────────────

    public function productTiers(Product $product): JsonResponse
    {
        $tiers = $this->displayService->getDisplayableTiers($product);

        return response()->json([
            'success' => true,
            'data'    => [
                'product_id'       => $product->id,
                'product_name'     => $product->name,
                'has_any_warranty' => $this->displayService->hasAnyWarrantyOptions($product),
                'tiers'            => $tiers,
            ],
        ]);
    }

    public function calculatePrice(Product $product): JsonResponse
    {
        $supplierWarranty = $product->supplierWarranties()
            ->where('is_transferable', true)
            ->where('warranty_end_date', '>', now())
            ->first();

        $prices = $this->priceCalculator->calculate($product, $supplierWarranty);

        return response()->json([
            'success' => true,
            'data'    => $prices,
        ]);
    }

    // ── Customer ───────────────────────────────

    public function myWarranties(Request $request): JsonResponse
    {
        $warranties = WarrantySale::where('customer_id', $request->user()->id)
            ->with(['product:id,name,slug', 'orderDetail', 'activeClaim'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $warranties]);
    }

    public function showWarranty(WarrantySale $warrantySale): JsonResponse
    {
        $warrantySale->load(['product', 'order', 'claims.stages', 'claims.notes']);
        return response()->json(['success' => true, 'data' => $warrantySale]);
    }

    public function fileClaim(WarrantySale $warrantySale, Request $request): JsonResponse
    {
        $request->validate([
            'issue_description' => 'required|string|min:10',
            'issue_type'        => 'nullable|string',
            'attachments'       => 'nullable|array',
        ]);

        try {
            $claim = $this->warrantyService->fileClaim($warrantySale, $request->all());
            return response()->json(['success' => true, 'message' => 'Claim filed.', 'data' => $claim], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function myClaims(Request $request): JsonResponse
    {
        $claims = WarrantyClaim::where('customer_id', $request->user()->id)
            ->with(['product:id,name', 'currentStage'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $claims]);
    }

    public function showClaim(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->load(['product', 'stages', 'notes.user']);
        return response()->json(['success' => true, 'data' => $warrantyClaim]);
    }

    public function cancelClaim(WarrantyClaim $warrantyClaim): JsonResponse
    {
        if ($warrantyClaim->status_enum->isTerminal()) {
            return response()->json(['success' => false, 'message' => 'Claim already closed.'], 422);
        }

        $warrantyClaim->transitionTo(\App\Enums\WarrantyClaimStatus::CANCELLED, 'Cancelled by customer.');
        $warrantyClaim->warrantySale->update(['status' => \App\Enums\WarrantySaleStatus::ACTIVE->value]);

        return response()->json(['success' => true, 'message' => 'Claim cancelled.']);
    }

    // ── Web (Redirect) ────────────────────────

    public function fileClaimWeb(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'warranty_sale_id'  => 'required|exists:warranty_sales,id',
            'issue_description' => 'required|string|min:10',
            'issue_type'        => 'nullable|string',
        ]);

        $warrantySale = WarrantySale::findOrFail($request->warranty_sale_id);

        try {
            $claim = $this->warrantyService->fileClaim($warrantySale, $request->all());
            return redirect()->route('customer.warranty.track', $claim->id)
                ->with('success', 'Warranty claim filed successfully! Claim #: ' . $claim->claim_number);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelClaimWeb(Request $request): \Illuminate\Http\RedirectResponse
    {
        $claim = WarrantyClaim::findOrFail($request->claim_id);

        if ($claim->status_enum->isTerminal()) {
            return back()->with('error', 'Claim already closed.');
        }

        $claim->transitionTo(\App\Enums\WarrantyClaimStatus::CANCELLED, 'Cancelled by customer.');
        $claim->warrantySale->update(['status' => \App\Enums\WarrantySaleStatus::ACTIVE->value]);

        return redirect()->route('customer.account')->with('success', 'Claim cancelled.');
    }
}
