<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\WarrantyClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\SupplierWarranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use App\Services\WarrantyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarrantyAdminController extends Controller
{
    public function __construct(
        private WarrantyService $warrantyService,
    ) {}

    // ── Stats ──────────────────────────────────

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'total_warranties'   => WarrantySale::count(),
                'active_warranties'  => WarrantySale::where('status', 'active')->count(),
                'expired_warranties' => WarrantySale::where('status', 'expired')->count(),
                'pending_claims'     => WarrantyClaim::where('status', 'submitted')->count(),
                'active_claims'      => WarrantyClaim::active()->count(),
                'resolved_claims'    => WarrantyClaim::where('status', 'resolved')->count(),
            ],
        ]);
    }

    // ── Supplier Warranties ────────────────────

    public function index(): JsonResponse
    {
        $warranties = SupplierWarranty::with(['supplier:id,name', 'product:id,name'])
            ->latest()
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $warranties]);
    }

    public function show(SupplierWarranty $supplier): JsonResponse
    {
        $supplier->load(['supplier', 'product', 'purchaseItem.purchase']);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_item_id'  => 'required|exists:purchase_items,id',
            'product_id'        => 'required|exists:products,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'warranty_days'     => 'required|integer|min:0',
            'warranty_start_date' => 'nullable|date',
            'warranty_terms'    => 'nullable|string',
            'is_transferable'   => 'boolean',
        ]);

        $data['warranty_end_date'] = isset($data['warranty_start_date'])
            ? now()->parse($data['warranty_start_date'])->addDays($data['warranty_days'])
            : now()->addDays($data['warranty_days']);

        $warranty = SupplierWarranty::create($data);

        return response()->json(['success' => true, 'data' => $warranty], 201);
    }

    public function update(Request $request, SupplierWarranty $supplier): JsonResponse
    {
        $data = $request->validate([
            'warranty_days'   => 'integer|min:0',
            'warranty_terms'  => 'nullable|string',
            'is_transferable' => 'boolean',
        ]);

        $supplier->update($data);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    // ── Warranty Sales ─────────────────────────

    public function sales(Request $request): JsonResponse
    {
        $sales = WarrantySale::with(['product:id,name', 'customer:id,name,phone'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $sales]);
    }

    public function showSale(WarrantySale $warrantySale): JsonResponse
    {
        $warrantySale->load(['product', 'customer', 'order', 'claims']);
        return response()->json(['success' => true, 'data' => $warrantySale]);
    }

    public function voidSale(WarrantySale $warrantySale): JsonResponse
    {
        $this->warrantyService->voidWarranty($warrantySale);
        return response()->json(['success' => true, 'message' => 'Warranty voided.']);
    }

    // ── Claims ─────────────────────────────────

    public function claims(Request $request): JsonResponse
    {
        $claims = WarrantyClaim::with(['product:id,name', 'customer:id,name,phone'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $claims]);
    }

    public function showClaim(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->load(['product', 'customer', 'order', 'warrantySale', 'stages', 'notes.user']);
        return response()->json(['success' => true, 'data' => $warrantyClaim]);
    }

    public function reviewClaim(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->transitionTo(WarrantyClaimStatus::UNDER_REVIEW, 'Claim under admin review.');
        $this->warrantyService->advanceClaimStage($warrantyClaim, 'Review started.');
        return response()->json(['success' => true, 'message' => 'Claim moved to review.']);
    }

    public function approveClaim(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->transitionTo(WarrantyClaimStatus::APPROVED, 'Claim approved.');
        $this->warrantyService->advanceClaimStage($warrantyClaim, 'Claim approved.');
        return response()->json(['success' => true, 'message' => 'Claim approved.']);
    }

    public function rejectClaim(WarrantyClaim $warrantyClaim, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $warrantyClaim->update(['rejection_reason' => $request->reason]);
        $warrantyClaim->transitionTo(WarrantyClaimStatus::REJECTED, "Rejected: {$request->reason}");
        return response()->json(['success' => true, 'message' => 'Claim rejected.']);
    }

    public function advanceStage(WarrantyClaim $warrantyClaim, Request $request): JsonResponse
    {
        $this->warrantyService->advanceClaimStage($warrantyClaim, $request->note);
        return response()->json(['success' => true, 'message' => 'Stage advanced.']);
    }

    public function resolveClaim(WarrantyClaim $warrantyClaim, Request $request): JsonResponse
    {
        $warrantyClaim->transitionTo(WarrantyClaimStatus::RESOLVED, $request->resolution ?? 'Claim resolved.');
        return response()->json(['success' => true, 'message' => 'Claim resolved.']);
    }

    public function addNote(WarrantyClaim $warrantyClaim, Request $request): JsonResponse
    {
        $request->validate(['note' => 'required|string|min:2']);
        $note = $warrantyClaim->notes()->create([
            'note'    => $request->note,
            'user_id' => auth()->id(),
        ]);
        return response()->json(['success' => true, 'data' => $note], 201);
    }
}
