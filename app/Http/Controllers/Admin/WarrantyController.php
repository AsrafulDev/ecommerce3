<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\SupplierWarranty;
use App\Models\WarrantyClaim;
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
        $warrantySale->load(['product', 'customer', 'order', 'claims.stages', 'claims.notes']);
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
}
