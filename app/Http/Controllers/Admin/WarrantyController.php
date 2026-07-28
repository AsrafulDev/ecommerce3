<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\SupplierWarranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyChallan;
use App\Models\WarrantySale;
use App\Services\WarrantyDisplayService;
use App\Services\WarrantyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyController extends Controller
{
    public function __construct(
        private WarrantyService $warrantyService,
        private WarrantyDisplayService $displayService,
    ) {}

    // ── Dashboard ──────────────────────────────

    public function dashboard(): View
    {
        $stats = [
            'total_warranties'   => WarrantySale::count(),
            'active_warranties'  => WarrantySale::where('status', 'active')->count(),
            'expired_warranties' => WarrantySale::where('status', 'expired')->count(),
            'pending_claims'     => WarrantyClaim::pending()->count(),
            'active_claims'      => WarrantyClaim::active()->count(),
            'supplier_warranties'=> SupplierWarranty::where('is_transferable', true)
                ->where('warranty_end_date', '>', now())->count(),
        ];

        $expiringSoon = WarrantySale::where('status', 'active')
            ->where('warranty_end_date', '>', now())
            ->where('warranty_end_date', '<=', now()->addDays(7))
            ->with('product:id,name')
            ->limit(10)
            ->get();

        $recentClaims = WarrantyClaim::with(['product:id,name', 'customer:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return view('backEnd.warranty.dashboard', compact('stats', 'expiringSoon', 'recentClaims'));
    }

    // ── Supplier Warranties ────────────────────

    public function supplierIndex(): View
    {
        $warranties = SupplierWarranty::with(['supplier:id,name', 'product:id,name'])
            ->latest()
            ->paginate(30);

        return view('backEnd.warranty.supplier_index', compact('warranties'));
    }

    public function supplierStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_item_id'    => 'required|exists:purchase_items,id',
            'product_id'          => 'required|exists:products,id',
            'supplier_id'         => 'required|exists:suppliers,id',
            'warranty_days'       => 'required|integer|min:0',
            'warranty_start_date' => 'nullable|date',
            'warranty_terms'      => 'nullable|string',
            'is_transferable'     => 'boolean',
        ]);

        $data['warranty_end_date'] = isset($data['warranty_start_date'])
            ? now()->parse($data['warranty_start_date'])->addDays($data['warranty_days'])
            : now()->addDays($data['warranty_days']);

        SupplierWarranty::create($data);

        return back()->with('success', 'Supplier warranty added.');
    }

    // ── Product Warranty Tiers ─────────────────

    public function tierIndex(): View
    {
        $products = Product::with('warrantyTiers')->paginate(30);
        return view('backEnd.warranty.tier_index', compact('products'));
    }

    public function tierEdit(Product $product): View
    {
        $tiers = $product->warrantyTiers()->ordered()->get();
        $supplierWarranty = $product->supplierWarranties()
            ->where('is_transferable', true)
            ->where('warranty_end_date', '>', now())
            ->first();

        return view('backEnd.warranty.tier_edit', compact('product', 'tiers', 'supplierWarranty'));
    }

    public function tierUpdate(Request $request, Product $product): RedirectResponse
    {
        $tiers = $request->input('tiers', []);

        foreach ($tiers as $tierData) {
            ProductWarrantyTier::updateOrCreate(
                [
                    'product_id'    => $product->id,
                    'warranty_type' => $tierData['warranty_type'],
                ],
                [
                    'tier_name'     => $tierData['tier_name'],
                    'warranty_days' => $tierData['warranty_days'] ?? 0,
                    'price'         => $tierData['price'] ?? 0,
                    'is_active'     => isset($tierData['is_active']),
                    'badge'         => $tierData['badge'] ?? null,
                    'sort_order'    => $tierData['sort_order'] ?? 0,
                ]
            );
        }

        return back()->with('success', 'Warranty tiers updated.');
    }

    // ── Warranty Sales ─────────────────────────

    public function salesIndex(Request $request): View
    {
        $sales = WarrantySale::with(['product:id,name', 'customer:id,name,phone'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return view('backEnd.warranty.sales_index', compact('sales'));
    }

    public function salesShow(WarrantySale $warrantySale): View
    {
        $warrantySale->load(['product', 'customer', 'order', 'soldBy', 'stockBatch', 'purchase', 'claims.stages', 'claims.notes']);
        return view('backEnd.warranty.sales_show', compact('warrantySale'));
    }

    public function salesVoid(WarrantySale $warrantySale): RedirectResponse
    {
        $this->warrantyService->voidWarranty($warrantySale);
        return back()->with('success', 'Warranty voided.');
    }

    // ── Claims ─────────────────────────────────

    public function claimsIndex(Request $request): View
    {
        $claims = WarrantyClaim::with(['product:id,name', 'customer:id,name,phone'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return view('backEnd.warranty.claims_index', compact('claims'));
    }

    public function claimsShow(WarrantyClaim $warrantyClaim): View
    {
        $warrantyClaim->load(['product', 'customer', 'order', 'warrantySale', 'stages', 'notes.user']);
        return view('backEnd.warranty.claims_show', compact('warrantyClaim'));
    }

    public function claimsAction(WarrantyClaim $warrantyClaim, string $action, Request $request): RedirectResponse
    {
        $actions = [
            'review'  => \App\Enums\WarrantyClaimStatus::UNDER_REVIEW,
            'approve' => \App\Enums\WarrantyClaimStatus::APPROVED,
            'resolve' => \App\Enums\WarrantyClaimStatus::RESOLVED,
        ];

        if (!isset($actions[$action])) {
            return back()->with('error', 'Invalid action.');
        }

        $warrantyClaim->transitionTo($actions[$action], $request->note);
        $this->warrantyService->advanceClaimStage($warrantyClaim, $request->note);

        return back()->with('success', "Claim {$action}ed.");
    }

    public function claimsReject(WarrantyClaim $warrantyClaim, Request $request): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $warrantyClaim->update(['rejection_reason' => $request->reason]);
        $warrantyClaim->transitionTo(\App\Enums\WarrantyClaimStatus::REJECTED, "Rejected: {$request->reason}");

        return back()->with('success', 'Claim rejected.');
    }

    public function claimsAddNote(WarrantyClaim $warrantyClaim, Request $request): RedirectResponse
    {
        $request->validate(['note' => 'required|string|min:2']);
        $warrantyClaim->notes()->create([
            'note'    => $request->note,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Note added.');
    }

    // ── Pipeline Actions ──────────────────────

    public function receiveProduct(Request $request, WarrantyClaim $warrantyClaim)
    {
        $request->validate([
            'condition'   => 'required|string',
            'accessories' => 'nullable|string',
            'notes'       => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateReceiveChallan($warrantyClaim, $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Product received. Challan #' . $challan->challan_no . ' generated.',
                'challan'   => $challan,
                'print_url' => route('admin.warranty.challans.print', $challan),
            ]);
        }

        return back()->with('success', 'Product received. Challan #' . $challan->challan_no . ' generated.');
    }

    public function sendToSupplier(Request $request, WarrantyClaim $warrantyClaim)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'courier'     => 'nullable|string',
            'tracking_id' => 'nullable|string',
            'notes'       => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateSendToSupplierChallan($warrantyClaim, $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Product sent to supplier. Challan #' . $challan->challan_no . ' generated.',
                'challan'   => $challan,
                'print_url' => route('admin.warranty.challans.print', $challan),
            ]);
        }

        return back()->with('success', 'Sent to supplier. Challan #' . $challan->challan_no . ' generated.');
    }

    public function supplierReturn(Request $request, WarrantyClaim $warrantyClaim)
    {
        $request->validate([
            'return_type'             => 'required|in:repaired,replaced,refunded',
            'replacement_sn'          => 'nullable|string|max:100',
            'supplier_return_challan' => 'nullable|string|max:50',
            'supplier_charge'         => 'nullable|numeric|min:0',
            'notes'                   => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateSupplierReturnChallan($warrantyClaim, $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Supplier return recorded. Challan #' . $challan->challan_no . ' generated.',
                'challan'   => $challan,
                'print_url' => route('admin.warranty.challans.print', $challan),
            ]);
        }

        return back()->with('success', 'Supplier return recorded. Challan #' . $challan->challan_no . ' generated.');
    }

    public function readyForDelivery(Request $request, WarrantyClaim $warrantyClaim)
    {
        $warrantyClaim->update([
            'status'                => \App\Enums\WarrantyClaimStatus::READY_FOR_DELIVERY->value,
            'ready_for_delivery_at' => now(),
        ]);

        $warrantyClaim->stages()->create([
            'stage'        => 'ready_for_return',
            'status'       => 'completed',
            'notes'        => 'Product ready for delivery to customer.',
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Marked ready for delivery.');
    }

    public function deliverToCustomer(Request $request, WarrantyClaim $warrantyClaim)
    {
        $request->validate([
            'delivery_method' => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateDeliveryChallan($warrantyClaim, $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Product delivered. Challan #' . $challan->challan_no . ' generated.',
                'challan'   => $challan,
                'print_url' => route('admin.warranty.challans.print', $challan),
            ]);
        }

        return back()->with('success', 'Delivered to customer. Challan #' . $challan->challan_no . ' generated.');
    }

    public function challans(WarrantyClaim $warrantyClaim): View
    {
        $challans = $warrantyClaim->challans()->latest()->get();
        return view('backEnd.warranty.claim_challans', compact('warrantyClaim', 'challans'));
    }

    public function printChallan(WarrantyChallan $challan): View
    {
        $challan->load('warrantyClaim.product', 'warrantyClaim.customer', 'warrantyClaim.warrantySale');
        return view('backEnd.warranty.challan_print', compact('challan'));
    }

    public function fileClaimForCustomer(Request $request): RedirectResponse
    {
        $request->validate([
            'warranty_sale_id'  => 'required|exists:warranty_sales,id',
            'issue_description' => 'required|string|min:5',
            'issue_type'        => 'required|string',
        ]);

        $warrantySale = \App\Models\WarrantySale::findOrFail($request->warranty_sale_id);
        $data = [
            'issue_description' => $request->issue_description,
            'issue_type'        => $request->issue_type,
            'customer_id'       => $warrantySale->customer_id,
            'attachments'       => $request->attachments ?? [],
        ];

        $claim = $this->warrantyService->fileClaim($warrantySale, $data);

        return redirect()->route('admin.warranty.claims.show', $claim)
            ->with('success', 'Claim filed on behalf of customer.');
    }
}
