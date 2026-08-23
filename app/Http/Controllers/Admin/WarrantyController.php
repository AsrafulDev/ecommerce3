<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\DamageStatus;
use App\Enums\WarrantyClaimStatus;
use App\Models\DamageProduct;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierWarranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimReminder;
use App\Models\WarrantyClaimStage;
use App\Models\WarrantyClaimStageAttachment;
use App\Models\WarrantyChallan;
use App\Models\WarrantySale;
use App\Services\StockManagementService;
use App\Services\WarrantyDisplayService;
use App\Services\WarrantyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Brian2694\Toastr\Facades\Toastr;
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

        // 🆕 Total claim count for utilization
        $stats['total_claims'] = WarrantyClaim::count();

        // 🆕 Supplier-wise warranty stats
        $supplierWarrantyStats = \App\Models\SupplierWarranty::with('supplier:id,name')
            ->where('is_transferable', true)
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($group) {
                $supplierId = $group->first()->supplier_id;
                return [
                    'supplier_name' => $group->first()->supplier->name ?? 'Unknown',
                    'available'     => $group->where('warranty_end_date', '>', now())->count(),
                    'total'         => $group->count(),
                    'sold'          => \App\Models\WarrantySale::whereIn('supplier_warranty_id', $group->pluck('id'))->count(),
                    'claims'        => \App\Models\WarrantyClaim::whereIn('warranty_sale_id',
                        \App\Models\WarrantySale::whereIn('supplier_warranty_id', $group->pluck('id'))->pluck('id')
                    )->count(),
                ];
            })
            ->sortByDesc('sold')
            ->take(10)
            ->values()
            ->toArray();

        // 🆕 Reminder tasks for Today / Tomorrow / Overdue
        $todayTasks    = WarrantyClaimReminder::with('warrantyClaim.product')
            ->dueToday()->get();
        $tomorrowTasks = WarrantyClaimReminder::with('warrantyClaim.product')
            ->dueTomorrow()->get();
        $overdueTasks  = WarrantyClaimReminder::with('warrantyClaim.product')
            ->overdue()->get();

        // 🆕 New (unreviewed) claims from customers
        $newClaims = WarrantyClaim::with(['product:id,name', 'customer:id,name,phone'])
            ->where('status', 'submitted')
            ->latest()
            ->get();

        return view('backEnd.warranty.dashboard', compact(
            'stats',
            'expiringSoon',
            'recentClaims',
            'supplierWarrantyStats',
            'todayTasks',
            'tomorrowTasks',
            'overdueTasks',
            'newClaims'
        ));
    }

    // ── Supplier Warranties ────────────────────

    public function supplierIndex(Request $request): View
    {
        $warranties = SupplierWarranty::with([
                'supplier',
                'product',
                'purchaseItem.purchase',
                'warrantySales' => fn($q) => $q->select('id', 'supplier_warranty_id')->withCount('claims'),
            ])
            // 🔍 Keyword: supplier name / product name / barcode / terms / notes
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('supplier', fn($x) => $x->where('name', 'like', "%{$s}%"))
                       ->orWhereHas('product', fn($x) => $x->where('name', 'like', "%{$s}%")
                                                        ->orWhere('barcode', 'like', "%{$s}%"))
                       ->orWhere('warranty_terms', 'like', "%{$s}%")
                       ->orWhere('notes', 'like', "%{$s}%");
                });
            })
            // 🔍 Batch no: matches stock_batches.batch_no for the same purchase+product
            ->when($request->filled('batch'), function ($q) use ($request) {
                $b = $request->input('batch');
                $q->whereHas('purchaseItem', function ($qq) use ($b) {
                    $qq->whereExists(function ($sub) use ($b) {
                        $sub->selectRaw(1)
                            ->from('stock_batches')
                            ->whereColumn('stock_batches.purchase_id', 'purchase_items.purchase_id')
                            ->whereColumn('stock_batches.product_id', 'purchase_items.product_id')
                            ->where('stock_batches.batch_no', 'like', "%{$b}%");
                    });
                });
            })
            ->when($request->filled('supplier'), fn($q) => $q->where('supplier_id', $request->input('supplier')))
            ->when($request->filled('type'), fn($q) => $q->where('warranty_type', $request->input('type')))
            ->when($request->filled('transferable'), fn($q) => $q->where('is_transferable', $request->boolean('transferable')))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'active') {
                    $q->where(function ($qq) {
                        $qq->where('warranty_end_date', '>=', now()->startOfDay())
                           ->orWhereNull('warranty_end_date');
                    });
                } elseif ($request->input('status') === 'expired') {
                    $q->where('warranty_end_date', '<', now()->startOfDay());
                }
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $suppliers     = Supplier::orderBy('name')->get(['id', 'name']);
        $products      = Product::orderBy('name')->get(['id', 'name']);
        $purchaseItems = PurchaseItem::with('product:id,name')->latest()->get();
        $batches       = \App\Models\StockBatch::with('product:id,name', 'supplier:id,name')
                            ->where('type', 'in')
                            ->where('remaining_qty', '>', 0)
                            ->latest()
                            ->get(['id', 'product_id', 'supplier_id', 'batch_no', 'remaining_qty']);

        return view('backEnd.warranty.supplier_index', compact('warranties', 'suppliers', 'products', 'purchaseItems', 'batches'));
    }

    public function supplierStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_item_id'    => 'nullable|exists:purchase_items,id',
            'batch_id'            => 'nullable|exists:stock_batches,id',
            'product_id'          => 'required|exists:products,id',
            'supplier_id'         => 'required|exists:suppliers,id',
            'warranty_days'       => 'required|integer|min:0',
            'warranty_start_date' => 'nullable|date',
            'warranty_terms'      => 'nullable|string',
            'notes'               => 'nullable|string',
            'is_transferable'     => 'boolean',
        ]);

        $data['warranty_days'] = (int) ($data['warranty_days'] ?? 0);

        $data['warranty_end_date'] = isset($data['warranty_start_date'])
            ? now()->parse($data['warranty_start_date'])->addDays($data['warranty_days'])
            : now()->addDays($data['warranty_days']);

        $supplierWarranty = SupplierWarranty::create($data);

        log_activity('warranty', 'create', 'Added supplier warranty: ' . ($supplierWarranty->product->name ?? 'Product #' . $data['product_id']) . ' (' . $data['warranty_days'] . ' days)', $supplierWarranty, $data);

        return back()->with('success', 'Supplier warranty added.');
    }

    public function supplierUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_warranty_id' => 'required|exists:supplier_warranties,id',
            'purchase_item_id'     => 'nullable|exists:purchase_items,id',
            'batch_id'             => 'nullable|exists:stock_batches,id',
            'product_id'           => 'required|exists:products,id',
            'supplier_id'          => 'required|exists:suppliers,id',
            'warranty_days'        => 'required|integer|min:0',
            'warranty_start_date'  => 'nullable|date',
            'warranty_terms'       => 'nullable|string',
            'notes'                => 'nullable|string',
            'is_transferable'      => 'boolean',
        ]);

        $supplierWarranty = SupplierWarranty::findOrFail($request->supplier_warranty_id);

        $data['warranty_days'] = (int) ($data['warranty_days'] ?? 0);

        $data['warranty_end_date'] = isset($data['warranty_start_date'])
            ? now()->parse($data['warranty_start_date'])->addDays($data['warranty_days'])
            : now()->addDays($data['warranty_days']);

        $supplierWarranty->update($data);

        log_activity('warranty', 'update', 'Updated supplier warranty #' . $supplierWarranty->id . ' (' . $data['warranty_days'] . ' days)', $supplierWarranty, $data);

        return back()->with('success', 'Supplier warranty updated.');
    }

    public function supplierDestroy(SupplierWarranty $supplierWarranty): RedirectResponse
    {
        log_activity('warranty', 'delete', 'Deleted supplier warranty #' . $supplierWarranty->id . ' for ' . ($supplierWarranty->product->name ?? 'product'), $supplierWarranty);

        $supplierWarranty->delete();

        return back()->with('success', 'Supplier warranty deleted.');
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
        $search = trim((string) $request->search);
        $status = $request->status;
        $type   = $request->type;

        $sales = WarrantySale::with([
                'product:id,name,barcode',
                'customer:id,name,phone',
                'order:id,invoice_id',
                'purchase.supplier:id,name,phone',
                'stockBatch.supplier:id,name,phone',
                'supplierWarranty.supplier:id,name,phone',
            ])
            ->withCount('claims')
            ->when($status, fn($q, $s) => $q->where('status', $s))
            ->when($type, fn($q, $t) => $q->where('warranty_type', $t))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    // Phone number
                    $q->whereHas('customer', fn($qq) => $qq->where('phone', 'like', "%{$search}%"))
                      // Order id / sales invoice id
                      ->orWhere('order_id', 'like', "%{$search}%")
                      ->orWhereHas('order', fn($qq) => $qq->where('id', 'like', "%{$search}%")
                                                    ->orWhere('invoice_id', 'like', "%{$search}%"))
                      // Product id / barcode / sku
                      ->orWhere('product_id', 'like', "%{$search}%")
                      ->orWhereHas('product', fn($qq) => $qq->where('id', 'like', "%{$search}%")
                                                    ->orWhere('barcode', 'like', "%{$search}%")
                                                    ->orWhereHas('variantPrices', fn($qv) => $qv
                                                        ->where('barcode', 'like', "%{$search}%")
                                                        ->orWhere('sku', 'like', "%{$search}%")))
                      // Purchase invoice no
                      ->orWhereHas('purchase', fn($qq) => $qq->where('invoice_no', 'like', "%{$search}%"))
                      // Serial number(s)
                      ->orWhere('serial_numbers', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('backEnd.warranty.sales_index', compact('sales'));
    }

    public function salesShow(WarrantySale $warrantySale): View
    {
        $warrantySale->load([
            'product',
            'customer',
            'order.orderdetails',
            'soldBy',
            'stockBatch.supplier',
            'purchase.supplier',
            'supplierWarranty.supplier',
            'claims.stages',
            'claims.notes',
        ]);
        return view('backEnd.warranty.sales_show', compact('warrantySale'));
    }

    public function salesVoid(WarrantySale $warrantySale): RedirectResponse
    {
        $this->warrantyService->voidWarranty($warrantySale);
        log_activity('warranty', 'void', 'Voided warranty sale #' . $warrantySale->id, $warrantySale);
        return back()->with('success', 'Warranty voided.');
    }

    // ── Claims ─────────────────────────────────

    public function claimsIndex(Request $request): View
    {
        $claims = WarrantyClaim::with(['product:id,name,barcode', 'customer:id,name,phone'])
            // 🔍 Keyword: claim # / issue description / customer name+phone / product name+barcode
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->where('claim_number', 'like', "%{$s}%")
                       ->orWhere('issue_description', 'like', "%{$s}%")
                       ->orWhereHas('customer', fn($x) => $x->where('name', 'like', "%{$s}%")
                                                           ->orWhere('phone', 'like', "%{$s}%"))
                       ->orWhereHas('product', fn($x) => $x->where('name', 'like', "%{$s}%")
                                                         ->orWhere('barcode', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('backEnd.warranty.claims_index', compact('claims'));
    }

    public function claimsShow(WarrantyClaim $warrantyClaim): View
    {
        $load = ['product', 'customer', 'order', 'warrantySale', 'stages', 'notes.user', 'reminders', 'damageProducts'];
        // Only eager-load per-step attachments if the migration has been run
        if (\Illuminate\Support\Facades\Schema::hasTable('warranty_claim_stage_attachments')) {
            $load[] = 'stages.attachments';
        }
        $warrantyClaim->load($load);

        // 🔎 Suppliers who actually supply THIS product (via purchases or stock batches)
        $productSuppliers = \App\Models\Supplier::where(function ($q) use ($warrantyClaim) {
            $q->whereHas('purchases.items', function ($qq) use ($warrantyClaim) {
                $qq->where('product_id', $warrantyClaim->product_id);
            })->orWhereHas('stockBatches', function ($qq) use ($warrantyClaim) {
                $qq->where('product_id', $warrantyClaim->product_id);
            });
        })->orderBy('name')->get();

        return view('backEnd.warranty.claims_show', compact('warrantyClaim', 'productSuppliers'));
    }

    public function claimsAction(WarrantyClaim $warrantyClaim, string $action, Request $request): RedirectResponse
    {
        $actions = [
            'review'        => \App\Enums\WarrantyClaimStatus::UNDER_REVIEW,
            'approve'       => \App\Enums\WarrantyClaimStatus::APPROVED,
            'await-product' => \App\Enums\WarrantyClaimStatus::AWAITING_PRODUCT,
            'resolve'       => \App\Enums\WarrantyClaimStatus::RESOLVED,
        ];

        if (!isset($actions[$action])) {
            return back()->with('error', 'Invalid action.');
        }

        $warrantyClaim->transitionTo($actions[$action], $request->note);
        $this->warrantyService->advanceClaimStage($warrantyClaim, $request->note);

        log_activity('warranty', 'claim_' . $action, 'Claim ' . $warrantyClaim->claim_number . ' → ' . $actions[$action]->value, $warrantyClaim);

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

    // ── Per-step Attachments ──────────────────

    /**
     * Attach an image/PDF to a specific claim step (a warranty_claim_stage).
     * Accepts either a Media Gallery selection (attachment_path) or direct
     * uploads (attachment_files[]). Files are stored in the shared media
     * folder public/uploads/media/warranty/ — images + PDF only (security).
     */
    public function storeStageAttachment(Request $request, WarrantyClaimStage $stage): RedirectResponse
    {
        $request->validate([
            'attachment_path'    => 'nullable|string',
            'attachment_files'   => 'nullable|array|max:5',
            'attachment_files.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,bmp,svg,avif,pdf|max:10240',
        ]);

        $created = 0;

        // Direct uploads → save into the shared media/warranty folder
        if ($request->hasFile('attachment_files')) {
            $dir = public_path('uploads/media/warranty');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'pdf'];
            foreach ((array) $request->file('attachment_files', []) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $ext = strtolower($file->getClientOriginalExtension());
                if (!in_array($ext, $allowed, true)) {
                    continue;
                }
                $base = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'attachment';
                $name = time() . '-' . uniqid() . '-' . $base . '.' . $ext;
                try {
                    $file->move($dir, $name);
                } catch (\Throwable $e) {
                    continue;
                }
                WarrantyClaimStageAttachment::create([
                    'warranty_claim_stage_id' => $stage->id,
                    'file_path'               => 'public/uploads/media/warranty/' . $name,
                    'file_name'               => $file->getClientOriginalName(),
                    'file_type'               => $ext,
                    'uploaded_by'             => auth()->id(),
                ]);
                $created++;
            }
        }

        // Media Gallery selection (single relative path e.g. public/uploads/media/...)
        if ($request->filled('attachment_path')) {
            $path = trim($request->input('attachment_path'));
            WarrantyClaimStageAttachment::create([
                'warranty_claim_stage_id' => $stage->id,
                'file_path'               => $path,
                'file_name'               => basename($path),
                'file_type'               => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                'uploaded_by'             => auth()->id(),
            ]);
            $created++;
        }

        if ($created > 0) {
            Toastr::success($created . ' attachment(s) added.', 'Success');
        } else {
            Toastr::error('No valid attachment selected (images & PDF only).', 'Error');
        }

        return back();
    }

    /** Remove a per-step attachment record (does NOT delete the shared media file). */
    public function deleteStageAttachment(WarrantyClaimStageAttachment $attachment): RedirectResponse
    {
        $attachment->delete();
        Toastr::success('Attachment removed.', 'Success');
        return back();
    }

    // ── Pipeline Actions ──────────────────────

    /**
     * Check if a challan of the given type already exists for this claim.
     * Used to enforce "one challan per type" per claim.
     */
    protected function challanTypeExists(WarrantyClaim $claim, string $type): bool
    {
        return WarrantyChallan::where('warranty_claim_id', $claim->id)
            ->where('challan_type', $type)
            ->exists();
    }

    public function receiveProduct(Request $request, WarrantyClaim $warrantyClaim)
    {
        if ($this->challanTypeExists($warrantyClaim, 'receive')) {
            return back()->with('error', 'A Product Receive challan already exists for this claim.');
        }

        $request->validate([
            'condition'          => 'required|string',
            'accessories'        => 'nullable|string',
            'notes'              => 'nullable|string',
            'product_image'      => 'nullable|image|max:5120',
            'product_image_url'  => 'nullable|string',
        ]);

        // Handle image — uploaded file OR Media Gallery selection
        if ($request->filled('product_image_url')) {
            $warrantyClaim->notes()->create([
                'note'    => 'Product image (Media Gallery): ' . $request->input('product_image_url'),
                'user_id' => auth()->id(),
            ]);
        } elseif ($request->hasFile('product_image')) {
            $path = $request->file('product_image')->store('warranty-claims', 'public');
            $warrantyClaim->notes()->create([
                'note'    => 'Product image uploaded: ' . asset('storage/' . $path),
                'user_id' => auth()->id(),
            ]);
        }

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
        if ($this->challanTypeExists($warrantyClaim, 'send_to_supplier')) {
            return back()->with('error', 'A Send to Supplier challan already exists for this claim.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse'   => 'nullable|string|max:100',
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
        if ($this->challanTypeExists($warrantyClaim, 'receive_return')) {
            return back()->with('error', 'A Supplier Return challan already exists for this claim.');
        }

        $request->validate([
            'return_type'             => 'required|in:repaired,replaced,refunded',
            'replacement_sn'          => 'nullable|string|max:100',
            'supplier_return_challan' => 'nullable|string|max:50',
            'supplier_charge'         => 'nullable|numeric|min:0',
            'notes'                   => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateSupplierReturnChallan($warrantyClaim, $request->all());

        // ⏰ Inline reminder (supplier return due)
        $this->createReminderFromRequest($warrantyClaim, $request);

        // 💰 Supplier charge → auto Expense (default checked)
        $supplierCharge = (float) $request->supplier_charge;
        $addToExpenses  = $request->boolean('add_to_expenses', true);

        if ($supplierCharge > 0 && $addToExpenses) {
            $expense = Expense::create([
                'title'        => 'Warranty supplier charge — Claim #' . $warrantyClaim->claim_number,
                'amount'       => $supplierCharge,
                'expense_date' => now()->toDateString(),
                'category'     => 'warranty',
                'note'         => 'Supplier charge for product ' . ($warrantyClaim->product->name ?? 'N/A'),
                'created_by'   => auth()->id(),
            ]);

            $fund = FundTransaction::create([
                'direction' => 'out',
                'source'    => 'expense',
                'source_id' => $expense->id,
                'amount'    => $supplierCharge,
                'note'      => 'Warranty expense — Claim #' . $warrantyClaim->claim_number,
                'created_by'=> auth()->id(),
            ]);

            $expense->update(['fund_transaction_id' => $fund->id]);
            $warrantyClaim->update(['supplier_expense_id' => $expense->id]);

            $warrantyClaim->notes()->create([
                'user_id' => auth()->id(),
                'note'    => 'Supplier charge ৳' . number_format($supplierCharge, 2) . ' added to expenses.',
            ]);
        } elseif ($supplierCharge > 0) {
            $warrantyClaim->update(['supplier_charge' => $supplierCharge]);
        }

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
        if ($this->challanTypeExists($warrantyClaim, 'delivery')) {
            return back()->with('error', 'A Customer Delivery challan already exists for this claim.');
        }

        $request->validate([
            'delivery_method' => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        $challanService = app(\App\Services\WarrantyChallanService::class);
        $challan = $challanService->generateDeliveryChallan($warrantyClaim, $request->all());

        // ⏰ Inline reminder (customer delivery due)
        $this->createReminderFromRequest($warrantyClaim, $request);

        // 💰 Customer charge → auto Earning (default checked)
        $customerCharge = (float) ($request->customer_charge ?? $warrantyClaim->customer_charge ?? 0);
        $applyToEarnings = $request->boolean('apply_to_earnings', true);

        if ($customerCharge > 0 && $applyToEarnings) {
            $fund = FundTransaction::create([
                'direction' => 'in',
                'source'    => 'warranty',
                'source_id' => $warrantyClaim->id,
                'amount'    => $customerCharge,
                'note'      => 'Warranty customer charge — Claim #' . $warrantyClaim->claim_number,
                'created_by'=> auth()->id(),
            ]);

            $warrantyClaim->update([
                'customer_charge'          => $customerCharge,
                'customer_earning_fund_id' => $fund->id,
            ]);

            $warrantyClaim->notes()->create([
                'user_id' => auth()->id(),
                'note'    => 'Customer charge ৳' . number_format($customerCharge, 2) . ' applied to earnings.',
            ]);
        } elseif ($customerCharge > 0) {
            $warrantyClaim->update(['customer_charge' => $customerCharge]);
        }

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

    /**
     * Manually update serial number (e.g., store replacement from own stock).
     */
    public function updateSerialNumber(Request $request, WarrantyClaim $warrantyClaim)
    {
        $request->validate([
            'new_serial_number' => 'required|string|max:100',
        ]);

        $warrantySale = $warrantyClaim->warrantySale;
        $oldSn = is_array($warrantySale->serial_numbers)
            ? implode(', ', $warrantySale->serial_numbers)
            : ($warrantySale->serial_numbers ?: 'N/A');

        $warrantySale->update(['serial_numbers' => [$request->new_serial_number]]);
        $warrantyClaim->update(['replacement_sn' => $request->new_serial_number]);

        $warrantyClaim->notes()->create([
            'user_id' => auth()->id(),
            'note'    => "Serial Number manually updated: {$oldSn} → {$request->new_serial_number}",
        ]);

        return back()->with('success', 'Serial number updated successfully.');
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

    public function downloadChallanPdf(WarrantyChallan $challan)
    {
        $challan->load('warrantyClaim.product', 'warrantyClaim.customer', 'warrantyClaim.warrantySale');

        $pdf = Pdf::loadView('backEnd.warranty.challan_pdf', compact('challan'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download($challan->challan_no . '.pdf');
    }

    /**
     * Delete a challan (e.g. a duplicate). Only removes the challan record —
     * the claim's status / challan_no references are left untouched.
     */
    public function destroyChallan(WarrantyChallan $challan): RedirectResponse
    {
        $claim = $challan->warrantyClaim;
        $challanNo = $challan->challan_no;
        $challan->delete();

        log_activity('warranty', 'challan_delete', "Challan {$challanNo} deleted for claim #{$claim->claim_number}", $claim);

        return back()->with('success', "Challan {$challanNo} deleted.");
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

        // Add admin note if provided
        if ($request->filled('admin_note')) {
            $claim->notes()->create([
                'note'    => '[Admin] ' . $request->admin_note,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('admin.warranty.claims.show', $claim)
            ->with('success', 'Claim filed on behalf of customer.');
    }

    // ── ⏰ Reminders ───────────────────────────

    public function storeReminder(WarrantyClaim $warrantyClaim, Request $request): RedirectResponse
    {
        $request->validate([
            'step'      => 'required|string',
            'label'     => 'required|string',
            'remind_at' => 'required|date',
            'note'      => 'nullable|string',
        ]);

        // One active reminder per step — replace instead of duplicate
        WarrantyClaimReminder::updateOrCreate(
            [
                'warranty_claim_id' => $warrantyClaim->id,
                'step'              => $request->step,
            ],
            [
                'label'      => $request->label,
                'remind_at'  => $request->remind_at,
                'note'       => $request->note,
                'status'     => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Reminder set.');
    }

    /**
     * Create a reminder from inline step-modal fields (remind_at + reminder_step + reminder_label).
     */
    protected function createReminderFromRequest(WarrantyClaim $warrantyClaim, Request $request): void
    {
        if (!$request->filled('remind_at') || !$request->filled('reminder_step')) {
            return;
        }

        WarrantyClaimReminder::updateOrCreate(
            [
                'warranty_claim_id' => $warrantyClaim->id,
                'step'              => $request->reminder_step,
            ],
            [
                'label'      => $request->reminder_label ?? ucwords(str_replace('_', ' ', $request->reminder_step)),
                'remind_at'  => $request->remind_at,
                'note'       => $request->reminder_note ?? null,
                'status'     => 'pending',
                'created_by' => auth()->id(),
            ]
        );
    }

    public function completeReminder(WarrantyClaimReminder $reminder): RedirectResponse
    {
        $reminder->update(['status' => 'done']);

        return back()->with('success', 'Reminder completed.');
    }

    // ── 💥 Instant Replacement (auto stock adjustment) ──

    public function giveReplacement(WarrantyClaim $warrantyClaim, Request $request)
    {
        $request->validate([
            'damage_type'           => 'required|in:partial,full',
            'replacement_product_id'=> 'required|exists:products,id',
            'replacement_sn'        => 'nullable|string|max:100',
            'condition_note'        => 'nullable|string|max:255',
            'accessories'           => 'nullable|string|max:255',
            // 🆕 Send damaged unit to supplier for warranty claim
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'warehouse'             => 'nullable|string|max:100',
            'courier'               => 'nullable|string|max:100',
            'tracking_id'           => 'nullable|string|max:100',
        ]);

        $product = Product::findOrFail($request->replacement_product_id);
        $stockService = app(StockManagementService::class);

        // 1️⃣ stock OUT the replacement unit given to the customer (1 unit)
        // Try batch-based stockOut first; fall back to simple decrement (same pattern as OrderController::handleStockChange)
        try {
            $stockService->stockOut($product, 1, [
                'type' => 'warranty_replacement',
                'id'   => $warrantyClaim->id,
            ]);
        } catch (\RuntimeException $e) {
            if ($product->stock < 1) {
                return back()->with('error', 'Insufficient stock for replacement: ' . $e->getMessage());
            }
            $product->decrement('stock', 1);
            \Illuminate\Support\Facades\Log::warning('Warranty replacement batch deduction failed, used fallback', [
                'product' => $product->id,
                'claim'   => $warrantyClaim->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // 2️⃣ record the received damaged unit into damage inventory
        $sale = $warrantyClaim->warrantySale;
        $rawSn = $sale?->serial_numbers;
        $originalSn = is_array($rawSn) ? implode(', ', $rawSn) : ($rawSn ?: null);

        $damage = DamageProduct::create([
            'warranty_claim_id'         => $warrantyClaim->id,
            'warranty_sale_id'          => $warrantyClaim->warranty_sale_id,
            'product_id'                => $warrantyClaim->product_id,
            'original_serial_number'    => $originalSn,
            'replacement_serial_number' => $request->replacement_sn,
            'damage_type'               => $request->damage_type,
            'status'                    => DamageStatus::ON_WARRANTY->value,
            'condition_note'            => $request->condition_note,
            'accessories'               => $request->accessories,
            'received_at'               => now(),
            'created_by'                => auth()->id(),
        ]);

        // 3️⃣ 🆕 Send the damaged unit to the supplier for warranty claim (with challan)
        //    Do this BEFORE setting READY_FOR_DELIVERY — the challan service sets
        //    SENT_TO_SUPPLIER, which we then override back to READY_FOR_DELIVERY
        //    (the customer's replacement is ready, the damaged unit is at the supplier).
        if ($request->filled('supplier_id')) {
            $challanService = app(\App\Services\WarrantyChallanService::class);
            $challan = $challanService->generateSendToSupplierChallan($warrantyClaim, $request->all(), $originalSn);

            // Damaged unit is now at the supplier
            $damage->update([
                'status'      => DamageStatus::SUPPLIER_HOLD->value,
                'supplier_id' => $request->supplier_id,
            ]);

            $warrantyClaim->notes()->create([
                'user_id' => auth()->id(),
                'note'    => "Damaged unit (SN: {$originalSn}) sent to supplier for warranty claim. Challan #{$challan->challan_no}.",
            ]);
        }

        // 4️⃣ update warranty sale / claim serial to the replacement unit
        if ($sale) {
            $sale->update(['serial_numbers' => [$request->replacement_sn]]);
        }
        $warrantyClaim->update([
            'replacement_sn' => $request->replacement_sn,
            'return_type'    => 'replaced',
            'status'         => WarrantyClaimStatus::READY_FOR_DELIVERY->value,
        ]);

        $warrantyClaim->stages()->create([
            'stage'        => 'replacement',
            'status'       => 'completed',
            'notes'        => 'Instant replacement issued. Damaged unit moved to damage stock (' . $request->damage_type . ').',
            'started_at'   => now(),
            'completed_at' => now(),
        ]);

        $warrantyClaim->notes()->create([
            'user_id' => auth()->id(),
            'note'    => "Instant replacement given (SN: {$request->replacement_sn}). Damaged unit → Damage stock #{$damage->id} ({$request->damage_type}).",
        ]);

        return back()->with('success', 'Replacement issued & stock adjusted.');
    }

    // ── 🔄 Damage Product status updates ──────

    public function updateDamageStatus(DamageProduct $damageProduct, Request $request)
    {
        $request->validate([
            'status'       => 'required|in:on_warranty,supplier_hold,in_service,resellable,unsellable,discarded',
            'service_cost' => 'nullable|numeric|min:0',
            'damage_cost'  => 'nullable|numeric|min:0',
            'resell_price' => 'nullable|numeric|min:0',
        ]);

        $oldStatus      = $damageProduct->status;
        $newStatus      = $request->status;
        $oldServiceCost = $damageProduct->service_cost;
        $oldDamageCost  = $damageProduct->damage_cost;
        $oldResellPrice = $damageProduct->resell_price;

        // 🆕 Always update price fields when provided (not gated on status transition)
        if ($request->filled('service_cost')) {
            $damageProduct->service_cost = (float) $request->service_cost;
        }
        if ($request->filled('damage_cost')) {
            $damageProduct->damage_cost = (float) $request->damage_cost;
        }
        if ($request->filled('resell_price')) {
            $damageProduct->resell_price = (float) $request->resell_price;
        }

        // ── RESELLABLE: stockIn + earning fund ──
        if ($newStatus === 'resellable') {
            if ($oldStatus !== 'resellable') {
                // First transition → stockIn + create earning
                app(StockManagementService::class)->stockIn($damageProduct->product, [
                    'quantity'       => 1,
                    'unit_cost'      => (float) $damageProduct->service_cost,
                    'reference_type' => 'warranty_repair',
                    'reference_id'   => $damageProduct->id,
                ]);

                // Create earning fund for the resale value
                if ((float) $damageProduct->resell_price > 0) {
                    $fund = FundTransaction::create([
                        'direction'  => 'in',
                        'source'     => 'warranty_resell',
                        'source_id'  => $damageProduct->id,
                        'amount'     => (float) $damageProduct->resell_price,
                        'note'       => "Warranty repair resale — Damage #{$damageProduct->id}",
                        'created_by' => auth()->id(),
                    ]);
                    $damageProduct->earning_fund_id = $fund->id;
                }
            } else {
                // Already resellable → update the earning fund if resell_price changed
                if ($damageProduct->earning_fund_id) {
                    FundTransaction::where('id', $damageProduct->earning_fund_id)->update([
                        'amount' => (float) $damageProduct->resell_price,
                        'note'   => "Warranty repair resale (updated) — Damage #{$damageProduct->id}",
                    ]);
                }
            }
        }

        // ── UNSELLABLE: write-off expense ──
        if ($newStatus === 'unsellable') {
            if ($oldStatus !== 'unsellable') {
                // First transition → create expense
                $damageProduct->disposed_at = now();
                if ((float) $damageProduct->damage_cost > 0) {
                    $expense = Expense::create([
                        'title'        => "Warranty write-off — Damage #{$damageProduct->id}",
                        'amount'       => (float) $damageProduct->damage_cost,
                        'expense_date' => now()->toDateString(),
                        'category'     => 'warranty_loss',
                        'note'         => 'Damaged product written off (Claim #' . ($damageProduct->warranty_claim_id ?? 'N/A') . ')',
                        'created_by'   => auth()->id(),
                    ]);

                    $fund = FundTransaction::create([
                        'direction' => 'out',
                        'source'    => 'expense',
                        'source_id' => $expense->id,
                        'amount'    => (float) $damageProduct->damage_cost,
                        'note'      => 'Warranty write-off — Damage #' . $damageProduct->id,
                        'created_by'=> auth()->id(),
                    ]);

                    $expense->update(['fund_transaction_id' => $fund->id]);
                    $damageProduct->expense_id = $expense->id;
                }
            } else {
                // Already unsellable → update the linked expense if damage_cost changed
                if ($damageProduct->expense_id) {
                    $e = Expense::find($damageProduct->expense_id);
                    if ($e && $e->category === 'warranty_loss') {
                        $e->update(['amount' => (float) $damageProduct->damage_cost]);
                        FundTransaction::where('id', $e->fund_transaction_id)->update([
                            'amount' => (float) $damageProduct->damage_cost,
                        ]);
                    }
                }
            }
        }

        // ── DISCARDED ──
        if ($newStatus === 'discarded' && $oldStatus !== 'discarded') {
            $damageProduct->disposed_at = now();
        }

        // 💰 Service cost → warranty repair expense (on ANY status change, not just resellable)
        if ((float) $damageProduct->service_cost > 0) {
            $existingExpense = $damageProduct->expense_id ? Expense::find($damageProduct->expense_id) : null;
            if ($existingExpense && $existingExpense->category === 'warranty_repair') {
                $existingExpense->update(['amount' => (float) $damageProduct->service_cost]);
                if ($existingExpense->fund_transaction_id) {
                    FundTransaction::where('id', $existingExpense->fund_transaction_id)
                        ->update(['amount' => (float) $damageProduct->service_cost]);
                }
            } else {
                $repairExpense = Expense::create([
                    'title'        => "Warranty repair cost — Damage #{$damageProduct->id}",
                    'amount'       => (float) $damageProduct->service_cost,
                    'expense_date' => now()->toDateString(),
                    'category'     => 'warranty_repair',
                    'note'         => 'Repair cost for damage product (Claim #' . ($damageProduct->warranty_claim_id ?? 'N/A') . ')',
                    'created_by'   => auth()->id(),
                ]);
                $repairFund = FundTransaction::create([
                    'direction' => 'out',
                    'source'    => 'expense',
                    'source_id' => $repairExpense->id,
                    'amount'    => (float) $damageProduct->service_cost,
                    'note'      => 'Warranty repair — Damage #' . $damageProduct->id,
                    'created_by'=> auth()->id(),
                ]);
                $repairExpense->update(['fund_transaction_id' => $repairFund->id]);
                $damageProduct->expense_id = $repairExpense->id;
            }
        }

        // ── Save ──
        $damageProduct->status = $newStatus;
        $damageProduct->save();

        // 📋 Activity log — who updated, what changed, when (any change)
        $changes = [];
        if ($oldStatus !== $newStatus) {
            $changes['status'] = ['old' => $oldStatus, 'new' => $newStatus];
        }
        if ((string) $oldServiceCost !== (string) $damageProduct->service_cost) {
            $changes['service_cost'] = ['old' => $oldServiceCost, 'new' => $damageProduct->service_cost];
        }
        if ((string) $oldDamageCost !== (string) $damageProduct->damage_cost) {
            $changes['damage_cost'] = ['old' => $oldDamageCost, 'new' => $damageProduct->damage_cost];
        }
        if ((string) $oldResellPrice !== (string) $damageProduct->resell_price) {
            $changes['resell_price'] = ['old' => $oldResellPrice, 'new' => $damageProduct->resell_price];
        }
        if (!empty($changes)) {
            $parts = [];
            foreach ($changes as $field => $c) {
                $parts[] = str_replace('_', ' ', $field) . ': ' . $c['old'] . ' → ' . $c['new'];
            }
            log_activity('damage', 'update', 'Damage #' . $damageProduct->id . ' — ' . implode(', ', $parts), $damageProduct, $changes);
        }

        if ($damageProduct->warrantyClaim) {
            $damageProduct->warrantyClaim->notes()->create([
                'user_id' => auth()->id(),
                'note'    => "Damage product #{$damageProduct->id} status: {$oldStatus} → {$newStatus} (service: {$damageProduct->service_cost}, loss: {$damageProduct->damage_cost})",
            ]);
        }

        return back()->with('success', 'Damage product status updated.');
    }

    // ── 🗄️ Damage Products list ───────────────

    public function damageIndex(Request $request): View
    {
        $damageProducts = DamageProduct::with(['product:id,name,stock', 'warrantyClaim:id,claim_number', 'logs', 'supplier:id,name'])
            // 🔍 Keyword: serial number(s) / condition note / product name+barcode / claim #
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->where('original_serial_number', 'like', "%{$s}%")
                       ->orWhere('replacement_serial_number', 'like', "%{$s}%")
                       ->orWhere('condition_note', 'like', "%{$s}%")
                       ->orWhereHas('product', fn($x) => $x->where('name', 'like', "%{$s}%")
                                                         ->orWhere('barcode', 'like', "%{$s}%"))
                       ->orWhereHas('warrantyClaim', fn($x) => $x->where('claim_number', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('type'), fn($q) => $q->where('damage_type', $request->input('type')))
            ->byStatus($request->status)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('backEnd.warranty.damage_index', compact('damageProducts'));
    }
}
