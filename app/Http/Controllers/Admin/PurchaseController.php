<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseLog;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\FundTransaction;
use App\Services\StockManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    /**
     * Check if current user is Admin (Super Admin or has Admin role)
     */
    private function isAdmin()
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return false;
        }

        // Super Admin (id=1) is always admin
        if ($user->id == 1) {
            return true;
        }

        // Check if user has Admin role
        $spatieRoles = $user->getRoleNames()->map(function($role) {
            return strtolower($role);
        })->toArray();

        return in_array('admin', $spatieRoles);
    }

    /**
     * Calculate current fund balance
     */
    private function calculateFundBalance()
    {
        $total_in  = FundTransaction::where('direction', 'in')->sum('amount');
        $total_out = FundTransaction::where('direction', 'out')->sum('amount');
        return $total_in - $total_out;
    }

    /**
     * Which of these serial numbers appear more than once in the same array —
     * used to reject duplicate SNs typed for the same purchase line.
     */
    private function findInternalDuplicates(array $serials): array
    {
        $counts = array_count_values($serials);
        return array_values(array_keys(array_filter($counts, fn ($c) => $c > 1)));
    }

    /**
     * Which of these serial numbers already exist for this product — checked
     * against `batch_sn_lists` (the queryable mirror of every batch's
     * sn_stock/sn_sold, see StockBatch::mirrorSnToList()), across ALL of the
     * product's batches, both currently in stock and already sold. SN must be
     * unique per product for its whole lifetime, not just within one batch.
     *
     * @param int|null $excludeBatchId  Skip this batch's own list (editing a
     *                                  batch's SNs in place isn't a clash with itself).
     */
    private function findDuplicateSerialsForProduct(int $productId, array $serials, ?int $excludeBatchId = null): array
    {
        $serials = array_values(array_unique(array_filter(array_map('trim', $serials))));
        if (empty($serials)) {
            return [];
        }

        $existing = [];
        \App\Models\BatchSnList::where('product_id', $productId)
            ->when($excludeBatchId, fn ($q) => $q->where('batch_id', '!=', $excludeBatchId))
            ->get(['stock_sn', 'sold_sn'])
            ->each(function ($row) use (&$existing) {
                $existing = array_merge($existing, (array) $row->stock_sn, (array) $row->sold_sn);
            });

        return array_values(array_intersect($serials, $existing));
    }
    /**
     * ==============================
     * Purchase List + Summary
     * AJAX Pagination Supported
     * ==============================
     */
    public function index(Request $request)
    {
        $currentYear  = now()->year;
        $currentMonth = now()->month;

        // SUMMARY
        $yearlyTotal = Purchase::whereYear('purchase_date', $currentYear)->sum('grand_total');
        $monthlyTotal = Purchase::whereYear('purchase_date', $currentYear)
                                ->whereMonth('purchase_date', $currentMonth)
                                ->sum('grand_total');
        $todayTotal = Purchase::whereDate('purchase_date', now()->toDateString())
                              ->sum('grand_total');
        $totalDue = Purchase::sum('due_amount');

        // QUERY
        $query = Purchase::with('supplier')->latest();

        if ($request->year) {
            $query->whereYear('purchase_date', $request->year);
        }
        if ($request->month) {
            $query->whereMonth('purchase_date', $request->month);
        }
        if ($request->from_date) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', "%{$term}%")
                  ->orWhereHas('supplier', function ($sq) use ($term) {
                      $sq->where('name', 'like', "%{$term}%")
                         ->orWhere('phone', 'like', "%{$term}%");
                  });
            });
        }

        $purchases = $query->paginate(10)->withQueryString();
        $draftToResume = null;
        if ($request->filled('draft')) {
            $draftToResume = Purchase::where('id', $request->draft)
                ->where('status', 0)
                ->where('created_by', Auth::id())
                ->first();
            // Draft was already published/deleted → send the user to a clean form
            if (!$draftToResume) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Draft not found — it may have already been published.');
            }
        }

        // AJAX RESPONSE (ONLY TABLE)
        if ($request->ajax()) {
            return view('backEnd.purchases.index', compact(
                'purchases'
            ))->render();
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        $productsJson = json_encode($products->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'stock' => $p->stock, 'barcode' => $p->barcode ?? '', 'hasVariants' => $p->variantPrices->count() > 0];
        }));
        $variantsJson = json_encode(\App\Models\ProductVariantPrice::with(['color','size'])->get()->groupBy('product_id'));

        // ⭐ Batch-wise pricing engine — per-product warranty tiers for the
        //    Warranty Pricing expander in each purchase row.
        $warrantyTiersJson = json_encode(
            \App\Models\ProductWarrantyTier::where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'product_id', 'tier_name', 'warranty_type', 'warranty_days', 'additional_cost'])
                ->groupBy('product_id')
        );

        return view('backEnd.purchases.index', compact(
            'currentYear',
            'currentMonth',
            'yearlyTotal',
            'monthlyTotal',
            'todayTotal',
            'totalDue',
            'purchases',
            'suppliers',
            'products',
            'warrantyTiersJson',
            'productsJson',
            'variantsJson',
            'draftToResume'
        ));
    }

    /**
     * ==============================
     * Store Purchase
     * ==============================
     */
    public function store(Request $request)
    {
        $request->validate([
            'draft_id'      => 'nullable|integer',
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'invoice_no'    => 'required|string|max:50',
            'items'         => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
            // ⭐ Batch-wise pricing payload (optional)
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'items.*.mrp'           => 'nullable|numeric|min:0',
            'items.*.activate_website' => 'nullable|boolean',
            'items.*.variant_prices' => 'nullable|array',
            'items.*.variant_prices.*.variant_id' => 'nullable|integer',
            'items.*.variant_prices.*.price'      => 'nullable|numeric|min:0',
            'items.*.variant_prices.*.old_price'  => 'nullable|numeric|min:0',
            'items.*.variant_prices.*.stock'      => 'nullable|integer|min:0',
            'items.*.wholesale_tiers' => 'nullable|array',
            'items.*.wholesale_tiers.*.variant_id'      => 'nullable|integer',
            'items.*.wholesale_tiers.*.min_quantity'    => 'nullable|integer|min:1',
            'items.*.wholesale_tiers.*.max_quantity'    => 'nullable|integer|min:1',
            'items.*.wholesale_tiers.*.wholesale_price' => 'nullable|numeric|min:0',
            'items.*.warranty_tiers' => 'nullable|array',
            'items.*.warranty_tiers.*.variant_id'      => 'nullable|integer',
            'items.*.warranty_tiers.*.tier_id'         => 'nullable|integer',
            'items.*.warranty_tiers.*.warranty_type'   => 'nullable|string|max:50',
            'items.*.warranty_tiers.*.tier_name'       => 'nullable|string|max:255',
            'items.*.warranty_tiers.*.warranty_days'   => 'nullable|integer|min:0',
            'items.*.warranty_tiers.*.additional_cost' => 'nullable|numeric',
            'items.*.warranty_tiers.*.is_active'       => 'nullable|boolean',
            'items.*.serial_numbers'      => 'nullable|array',
            'items.*.serial_numbers.*'    => 'nullable|string|max:255',
            'discount'      => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount'   => 'nullable|numeric|min:0',
        ]);

        // 🔢 Warranty → SN required: every unit with a supplier warranty needs a serial number.
        // 🔁 SN must be unique per product — no repeats within this same submission, and none
        //    already recorded (in stock or sold) against any earlier batch of that product.
        $snErrors = [];
        $seenInRequest = []; // product_id => [serial, ...] already claimed by an earlier item this submission
        foreach ((array) $request->items as $index => $item) {
            $pid     = $item['product_id'] ?? null;
            $qty     = (int) ($item['qty'] ?? 0);
            $serials = array_values(array_filter(array_map('trim', (array) ($item['serial_numbers'] ?? []))));

            if ((int) ($item['warranty_days'] ?? 0) > 0 && $qty > 0 && count($serials) < $qty) {
                $snErrors["items.$index.serial_numbers"] =
                    "Warranty requires a serial number (SN) for every unit — provide {$qty} SN(s) (got " . count($serials) . ').';
                continue;
            }

            if (!$pid || empty($serials)) {
                continue;
            }

            $internalDupes = $this->findInternalDuplicates($serials);
            if ($internalDupes) {
                $snErrors["items.$index.serial_numbers"] =
                    'Duplicate serial number(s) entered for this item: ' . implode(', ', $internalDupes);
                continue;
            }

            $clashInRequest = array_values(array_intersect($serials, $seenInRequest[$pid] ?? []));
            if ($clashInRequest) {
                $snErrors["items.$index.serial_numbers"] =
                    'Serial number(s) already used elsewhere in this purchase: ' . implode(', ', $clashInRequest);
                continue;
            }
            $seenInRequest[$pid] = array_merge($seenInRequest[$pid] ?? [], $serials);

            $existingClash = $this->findDuplicateSerialsForProduct((int) $pid, $serials);
            if ($existingClash) {
                $snErrors["items.$index.serial_numbers"] =
                    'Serial number(s) already exist for this product: ' . implode(', ', $existingClash);
            }
        }
        if ($snErrors) {
            return back()->withErrors($snErrors)->withInput();
        }

        $discount      = $request->discount ?? 0;
        $shipping_cost = $request->shipping_cost ?? 0;

        // Calculate totals from all items
        $totalQty = 0;
        $subtotal = 0;
        foreach ($request->items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? 0);
            $totalQty += $qty;
            $subtotal += $qty * $cost;
        }

        $grandTotal = $subtotal - $discount + $shipping_cost;
        $paid = min($grandTotal, (float) ($request->paid_amount ?? 0));
        $due  = $grandTotal - $paid;

        // ⭐ Phase 1.1 — publish inside ONE DB transaction so a mid-loop failure
        //    (bad supplier on item 2, tier error, …) can't leave a partial
        //    purchase (header/items/stock/fund). Rollback wipes everything.
        try {
            DB::beginTransaction();

            // CREATE PURCHASE
            $purchase = Purchase::create([
                'supplier_id'   => $request->supplier_id,
                'invoice_no'    => $request->invoice_no,
                'purchase_date' => $request->purchase_date,
                'total_qty'     => $totalQty,
                'subtotal'      => $subtotal,
                'discount'      => $discount,
                'shipping_cost' => $shipping_cost,
                'grand_total'   => $grandTotal,
                'paid_amount'   => $paid,
                'due_amount'    => $due,
                'note'          => $request->note,
                'status'        => 1,
                'created_by'    => Auth::id(),
            ]);

            // CREATE PURCHASE ITEMS + WARRANTY + STOCK per item
            foreach ($request->items as $item) {
                $qty   = (int) ($item['qty'] ?? 0);
                $cost  = (float) ($item['unit_cost'] ?? 0);
                $line  = $qty * $cost;
                $pid   = $item['product_id'];
                $vid   = $item['variant_id'] ?? null;

                // Load the product up-front — needed for warranty tiers AND stock below
                $product = Product::findOrFail($pid);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id'      => $purchase->id,
                    'product_id'       => $pid,
                    'variant_price_id' => $vid,
                    'qty'              => $qty,
                    'unit_cost'        => $cost,
                    'line_total'       => $line,
                    'custom_field'     => $item['custom_field'] ?? null,
                ]);

                // 🛡️ Warranty per item
                $wDays = (int) ($item['warranty_days'] ?? 0);
                $supplierWarranty = null;
                if ($wDays > 0) {
                    $wStart = $item['warranty_start'] ?? now()->format('Y-m-d');
                    $supplierWarranty = \App\Models\SupplierWarranty::create([
                        'purchase_item_id'   => $purchaseItem->id,
                        'product_id'         => $pid,
                        'supplier_id'        => $request->supplier_id,
                        'warranty_days'      => $wDays,
                        'warranty_start_date' => $wStart,
                        'warranty_end_date'  => \Carbon\Carbon::parse($wStart)->addDays($wDays),
                        'warranty_type'      => 'supplier_warranty',
                        'warranty_terms'     => $item['warranty_terms'] ?? null,
                        'is_transferable'    => (bool) ($item['transferable'] ?? true),
                    ]);

                    // ✅ Auto-generate product warranty tiers from supplier warranty
                    app(\App\Services\WarrantyService::class)->generateTiers($product, $supplierWarranty);

                    // Also update product supplier_price
                    \App\Models\Product::where('id', $pid)->update(['supplier_price' => $cost]);
                }

                // Stock
                $product->supplier_price = $cost;
                $product->save();
                $batch = app(StockManagementService::class)->stockIn($product, [
                    'quantity' => $qty, 'unit_cost' => $cost,
                    'selling_price'        => $item['selling_price'] ?? null,
                    'mrp'                  => $item['mrp'] ?? null,
                    'is_active_for_website'=> (bool) ($item['activate_website'] ?? false),
                    'supplier_id' => $request->supplier_id, 'purchase_id' => $purchase->id,
                    'variant_price_id' => $vid, 'reference_type' => 'purchase', 'reference_id' => $purchase->id,
                    'batch_no' => $item['batch_no'] ?? null,
                    'mfg_date' => $item['mfg_date'] ?? null,
                    'exp_date' => $item['exp_date'] ?? null,
                    'custom_field' => $item['custom_field'] ?? null,
                    'serial_numbers' => $item['serial_numbers'] ?? [],
                ]);

                // 🛡️ Link the supplier warranty to the created batch + variant, and flag the batch
                if ($supplierWarranty) {
                    $supplierWarranty->update([
                        'batch_id'   => $batch->id,
                        'variant_id' => $vid ?: null,
                    ]);
                    $batch->update(['has_purchase_warranty' => true]);
                }

                // ⭐ Batch-wise pricing payload (variant / wholesale / warranty)
                $this->persistBatchPricing($purchaseItem, $batch, $item);

                // 🟢 Activate as the website batch — when the admin ticks
                //    "Set as website active batch", OR when this is the product's
                //    first batch (so a freshly created batch shows on the site).
                //    Works with BATCH_WISE_PRICING on or off.
                $alreadyActive = \App\Models\StockBatch::where('product_id', $product->id)
                    ->where('is_active_for_website', true)
                    ->exists();
                if (!empty($item['activate_website']) || !$alreadyActive) {
                    app(\App\Services\PricingService::class)->setActiveWebsiteBatch($product, $batch->id);
                }
            }

            // SUPPLIER DUE
            $supplier = Supplier::findOrFail($request->supplier_id);
            $supplier->current_due += ($grandTotal - $paid);
            $supplier->save();

            // FUND PAYMENT
            if ($paid > 0) {
                $fund = FundTransaction::create([
                    'direction'  => 'out',
                    'source'     => 'supplier_payment',
                    'source_id'  => null,
                    'amount'     => $paid,
                    'note'       => 'Purchase payment: '.$purchase->invoice_no,
                    'created_by' => Auth::id(),
                ]);

                $payment = SupplierPayment::create([
                    'supplier_id'        => $supplier->id,
                    'purchase_id'        => $purchase->id,
                    'amount'             => $paid,
                    'payment_date'       => $request->purchase_date,
                    'method'             => 'fund',
                    'note'               => 'Initial payment',
                    'fund_transaction_id'=> $fund->id,
                    'created_by'         => Auth::id(),
                ]);

                $fund->source_id = $payment->id;
                $fund->save();
            }

            // 📝 Audit: purchase created (inside the tx — vanishes on rollback too)
            $fundAfter  = $this->calculateFundBalance();
            $fundBefore = $fundAfter + $paid; // this purchase only moved the fund OUT by $paid
            $balanceDiff = $fundAfter - $fundBefore;
            $balanceSign = ($balanceDiff >= 0) ? '+' : '';
            PurchaseLog::create([
                'purchase_id'         => $purchase->id,
                'action'              => 'create',
                'old_invoice_no'      => null,
                'new_invoice_no'      => $purchase->invoice_no,
                'old_purchase_date'   => null,
                'new_purchase_date'   => $purchase->purchase_date,
                'old_paid_amount'     => null,
                'new_paid_amount'     => $purchase->paid_amount,
                'old_grand_total'     => null,
                'new_grand_total'     => $purchase->grand_total,
                'old_note'            => null,
                'new_note'            => $purchase->note,
                'fund_balance_before' => $fundBefore,
                'fund_balance_after'  => $fundAfter,
                'description'         => "Purchase created: Invoice '{$purchase->invoice_no}' (Total: {$purchase->grand_total}, Paid: {$purchase->paid_amount}). Fund balance changed from {$fundBefore} to {$fundAfter} ({$balanceSign}{$balanceDiff})",
                'performed_by'        => Auth::id(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error(
                'Purchase create failed — all changes rolled back: '.$e->getMessage(),
                ['invoice_no' => $request->invoice_no, 'supplier_id' => $request->supplier_id, 'trace' => $e->getTraceAsString()]
            );

            return back()
                ->withErrors(['error' => 'Purchase could not be saved — no records were written. '.$e->getMessage()])
                ->withInput();
        }

        if ($request->filled('draft_id')) {
            Purchase::where('id', $request->draft_id)
                ->where('status', 0)
                ->where('created_by', Auth::id())
                ->delete();

            // Draft is gone now — send to the clean purchases page (not back to ?draft=..)
            return redirect()->route('purchases.index')
                ->with('success', 'Purchase created & stock updated!');
        }

        return back()->with('success','Purchase created & stock updated!');
    }

    /**
     * Save an incomplete purchase form without creating operational records.
     */
    public function saveDraft(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can save purchase drafts.');
        }

        $request->validate([
            'draft_id' => 'nullable|integer',
            'payload'  => 'required|string',
        ]);

        $payload = json_decode($request->payload, true);
        if (!is_array($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid draft data.'], 422);
        }

        $attributes = [
            'invoice_no'    => $payload['invoice_no'] ?? 'DRAFT-' . now()->format('YmdHis'),
            'purchase_date' => $payload['purchase_date'] ?? now()->toDateString(),
            'supplier_id'   => !empty($payload['supplier_id']) ? (int) $payload['supplier_id'] : null,
            'note'          => $payload['note'] ?? null,
            'draft_data'    => $payload,
            'status'        => 0,
            'created_by'    => Auth::id(),
            'total_qty'     => 0,
            'subtotal'      => 0,
            'discount'      => 0,
            'shipping_cost' => 0,
            'grand_total'   => 0,
            'paid_amount'   => 0,
            'due_amount'    => 0,
            'amount'        => 0,
        ];

        if ($request->filled('draft_id')) {
            $draft = Purchase::where('id', $request->draft_id)
                ->where('status', 0)
                ->where('created_by', Auth::id())
                ->firstOrFail();
            $draft->update($attributes);
        } else {
            $draft = Purchase::create($attributes);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => 'Draft saved.',
            'draft_id' => $draft->id,
        ]);
    }

    /**
     * Delete an unpublished purchase draft without touching stock or accounting.
     */
    public function destroyDraft($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can delete purchase drafts.');
        }

        Purchase::where('id', $id)
            ->where('status', 0)
            ->where('created_by', Auth::id())
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Purchase draft deleted.');
    }

    /**
     * ==============================
     * Pay Supplier Due
     * ==============================
     */
    public function payDue(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);

        $request->validate([
            'amount'       => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        if ($request->amount > $purchase->due_amount) {
            return back()->with('error','Pay amount cannot be greater than due.');
        }

        $fund = FundTransaction::create([
            'direction'  => 'out',
            'source'     => 'supplier_payment',
            'source_id'  => null,
            'amount'     => $request->amount,
            'note'       => 'Due payment: '.$purchase->invoice_no,
            'created_by' => Auth::id(),
        ]);

        $payment = SupplierPayment::create([
            'supplier_id'        => $purchase->supplier_id,
            'purchase_id'        => $purchase->id,
            'amount'             => $request->amount,
            'payment_date'       => $request->payment_date,
            'method'             => 'fund',
            'note'               => $request->note,
            'fund_transaction_id'=> $fund->id,
            'created_by'         => Auth::id(),
        ]);

        $fund->source_id = $payment->id;
        $fund->save();

        $purchase->paid_amount += $request->amount;
        $purchase->due_amount  -= $request->amount;
        $purchase->save();

        $supplier = $purchase->supplier;
        $supplier->current_due = max(0, $supplier->current_due - $request->amount);
        $supplier->save();

        return back()->with('success','Due payment successful!');
    }

    /**
     * ==============================
     * Purchase Return
     * ==============================
     */
    public function returnItem(Request $request, $itemId)
    {
        $item = PurchaseItem::with('purchase','product','variant')->findOrFail($itemId);

        $request->validate([
            'return_qty' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->return_qty;

        if ($qty > ($item->qty - $item->returned_qty)) {
            return back()->with('error','Return qty cannot be greater than remaining qty.');
        }

        $item->returned_qty += $qty;
        $item->save();

        $product = $item->product;

        // ⭐ Phase 2.6 — route the return through StockManagementService::stockOut
        //    (batch FIFO/LIFO deduction + COGS + a `purchase_return` trace row).
        //    NO manual remaining_qty / products.stock decrements.
        app(StockManagementService::class)->stockOut($product, $qty, [
            'type' => 'purchase_return',
            'id'   => $item->purchase_id,
        ]);

        // Reverse the purchase-time variant stock-in increment (denormalized copy).
        if ($item->variant) {
            $item->variant->decrement('stock', $qty);
        }

        // ⭐ Batch-wise pricing engine — keep website cache in sync and auto-advance
        //    the active batch if this return depleted it.
        if (config('pricing.batch_wise', false)) {
            $pricing = app(\App\Services\PricingService::class);
            $pricing->refreshProductCache($product);
            $pricing->advanceActiveBatchIfDepleted($product);
            app(StockManagementService::class)->syncStockFromBatches($product->id);
        }

        return back()->with('success','Purchase return processed & stock updated.');
    }

    /**
     * ==============================
     * Invoice
     * ==============================
     */
    public function invoice($id)
    {
        $purchase = Purchase::with(['supplier','items.product','items.variant','payments'])
                            ->findOrFail($id);
        return view('backEnd.purchases.invoice', compact('purchase'));
    }

    /**
     * ==============================
     * Download Invoice as PDF
     * ==============================
     */
    public function downloadInvoice($id)
    {
        $purchase = Purchase::with(['supplier','items.product','items.variant','payments'])
                            ->findOrFail($id);

        $pdf = Pdf::loadView('backEnd.purchases.invoice_pdf', compact('purchase'))
                  ->setPaper('a4', 'portrait');

        $filename = 'Invoice_' . ($purchase->invoice_no ?? $purchase->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * ==============================
     * Export CSV
     * ==============================
     */
    public function export(Request $request)
    {
        $query = Purchase::with('supplier')->orderBy('purchase_date','asc');

        if ($request->year) {
            $query->whereYear('purchase_date',$request->year);
        }
        if ($request->month) {
            $query->whereMonth('purchase_date',$request->month);
        }
        if ($request->from_date) {
            $query->whereDate('purchase_date','>=',$request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('purchase_date','<=',$request->to_date);
        }

        $purchases = $query->get();

        $filename = 'purchases_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($purchases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date','Invoice','Supplier','Total Qty','Grand Total','Paid','Due']);

            foreach ($purchases as $p) {
                fputcsv($handle, [
                    $p->purchase_date,
                    $p->invoice_no,
                    optional($p->supplier)->name,
                    $p->total_qty,
                    $p->grand_total,
                    $p->paid_amount,
                    $p->due_amount,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete Purchase (Admin only)
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can delete purchases.');
        }

        return DB::transaction(function () use ($id) {
            $purchase = Purchase::with(['items', 'payments'])->findOrFail($id);

            // Save purchase data for logging
            $old_invoice_no = $purchase->invoice_no;
            $old_purchase_date = $purchase->purchase_date;
            $old_paid_amount = $purchase->paid_amount;
            $old_grand_total = $purchase->grand_total;
            $old_note = $purchase->note;

            // Calculate fund balance before delete
            $fund_balance_before = $this->calculateFundBalance();

            // Calculate expected balance after delete
            // When purchase is deleted, all linked fund transactions (OUT) should be removed
            // So balance will increase by total paid amount
            $expected_balance_after = $fund_balance_before + $old_paid_amount;

            // Create log entry BEFORE deleting
            $balance_diff = $expected_balance_after - $fund_balance_before;
            $balance_sign = ($balance_diff > 0) ? '+' : '';
            $description = "Purchase deleted: Invoice '{$old_invoice_no}' (Paid: {$old_paid_amount}, Total: {$old_grand_total}). Fund balance changed from {$fund_balance_before} to {$expected_balance_after} ({$balance_sign}{$balance_diff})";

            PurchaseLog::create([
                'purchase_id' => $id,
                'action' => 'delete',
                'old_invoice_no' => $old_invoice_no,
                'new_invoice_no' => null,
                'old_purchase_date' => $old_purchase_date,
                'new_purchase_date' => null,
                'old_paid_amount' => $old_paid_amount,
                'new_paid_amount' => null,
                'old_grand_total' => $old_grand_total,
                'new_grand_total' => null,
                'old_note' => $old_note,
                'new_note' => null,
                'fund_balance_before' => $fund_balance_before,
                'fund_balance_after' => $expected_balance_after,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            // Delete linked fund transactions (supplier payments)
            foreach ($purchase->payments as $payment) {
                if ($payment->fund_transaction_id) {
                    $fund = FundTransaction::find($payment->fund_transaction_id);
                    if ($fund) {
                        $fund->delete();
                    }
                }
                $payment->delete();
            }

            // ── Stock ledger cleanup ──────────────────────────────────────────────
            // Remove this purchase's stock-in batches. stock_batches.remaining_qty is the
            // source of truth for products.stock (see plan.md) — after removing the batches
            // we recompute product/variant stock from whatever is left. This also avoids
            // orphaned, still-sellable batches left behind by a deleted purchase.
            $affectedProductIds = $purchase->items->pluck('product_id')->filter()->unique()->values();

            \App\Models\StockBatch::where('purchase_id', $id)
                ->where('type', 'in')
                ->delete(); // FK cascades to batch_variant/wholesale/warranty pricing rows

            foreach ($affectedProductIds as $productId) {
                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }
                $remaining = (int) \App\Models\StockBatch::where('product_id', $productId)
                    ->where('type', 'in')
                    ->where('remaining_qty', '>', 0)
                    ->sum('remaining_qty');

                if ($remaining > 0) {
                    // Other batches still hold stock — resync from the ledger.
                    app(StockManagementService::class)->syncStockFromBatches($productId);
                } else {
                    // Nothing left in the ledger — zero this product's stock copies.
                    $product->stock = 0;
                    $product->website_stock = 0;
                    $product->save();
                    \App\Models\ProductVariantPrice::where('product_id', $productId)->update(['stock' => 0]);
                }
            }

            // Update supplier due
            $supplier = $purchase->supplier;
            $supplier->current_due = max(0, $supplier->current_due - $purchase->due_amount);
            $supplier->save();

            // Delete purchase items
            $purchase->items()->delete();

            // Now delete the purchase
            $purchase->delete();

            // Verify balance after delete
            $actual_balance_after = $this->calculateFundBalance();

            return redirect()->route('purchases.index')
                             ->with('success', 'Purchase deleted successfully! Fund balance adjusted automatically.');
        });
    }

    /**
     * Purchase Logs / Report
     */
    public function logs(Request $request)
    {
        $query = PurchaseLog::with(['purchase', 'performedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Summary statistics
        $total_creates = PurchaseLog::where('action', 'create')->count();
        $total_edits = PurchaseLog::where('action', 'edit')->count();
        $total_deletes = PurchaseLog::where('action', 'delete')->count();

        return view('backEnd.purchases.logs', compact('logs', 'total_creates', 'total_edits', 'total_deletes'));
    }

    /**
     * ⭐ Batch-wise pricing engine.
     *
     * Persists the per-batch sell price payload captured on the purchase form:
     *   - batch_variant_prices     (per batch × variant)
     *   - batch_wholesale_prices   (per batch × variant × qty tier)
     *   - batch_warranty_tiers     (per batch × variant × tier)
     *   - purchase_item_prices     (price snapshot for history/invoices)
     *   - website batch activation (if requested)
     *
     * Only active when BATCH_WISE_PRICING=1 — legacy purchase flow is untouched.
     */
    private function persistBatchPricing(PurchaseItem $purchaseItem, \App\Models\StockBatch $batch, array $item): void
    {
        // Always persist the per-batch wholesale/warranty/variant pricing payload so
        // it is saved at purchase time regardless of the BATCH_WISE_PRICING flag.

        // 1) Variant prices (per batch)
        foreach (($item['variant_prices'] ?? []) as $vp) {
            if (empty($vp['variant_id']) || empty($vp['price'])) {
                continue;
            }
            \App\Models\BatchVariantPrice::updateOrCreate(
                ['stock_batch_id' => $batch->id, 'variant_price_id' => (int) $vp['variant_id']],
                [
                    'price'     => (float) $vp['price'],
                    'old_price' => isset($vp['old_price']) && $vp['old_price'] !== '' ? (float) $vp['old_price'] : null,
                    'stock'     => (int) ($vp['stock'] ?? $batch->remaining_qty),
                ]
            );
        }

        // 2) Wholesale tiers (per batch)
        foreach (($item['wholesale_tiers'] ?? []) as $wt) {
            \App\Models\BatchWholesalePrice::create([
                'stock_batch_id'   => $batch->id,
                'variant_price_id' => !empty($wt['variant_id']) ? (int) $wt['variant_id'] : null,
                'min_quantity'     => (int) ($wt['min_quantity'] ?? 1),
                'max_quantity'     => !empty($wt['max_quantity']) ? (int) $wt['max_quantity'] : null,
                'wholesale_price'  => (float) ($wt['wholesale_price'] ?? 0),
            ]);
        }

        // 3) Warranty tiers (per batch)
        //    Each row can reference an existing product tier (tier_id) OR create a
        //    brand-new product warranty option by type (warranty_type) right from
        //    the purchase — No Warranty / Supplier Warranty / Extended Warranty.
        foreach (($item['warranty_tiers'] ?? []) as $wt) {
            $tierId    = (int) ($wt['tier_id'] ?? 0);
            $variantId = !empty($wt['variant_id']) ? (int) $wt['variant_id'] : null;

            if (!$tierId) {
                $wType = $wt['warranty_type'] ?? null;
                if (!$wType) {
                    continue;
                }
                $tierName = trim((string) ($wt['tier_name'] ?? ''));
                if ($tierName === '') {
                    $tierName = \App\Enums\WarrantyType::tryFrom($wType)?->label()
                        ?? ucwords(str_replace('_', ' ', $wType));
                }
                $days = (int) ($wt['warranty_days'] ?? 0);
                $cost = (float) ($wt['additional_cost'] ?? 0);

                // Create the product-level tier once (keyed by type) — non-destructive.
                $productTier = \App\Models\ProductWarrantyTier::firstOrCreate(
                    ['product_id' => $batch->product_id, 'warranty_type' => $wType, 'variant_id' => null],
                    [
                        'tier_name'       => $tierName,
                        'warranty_days'   => $days,
                        'price'           => $cost,
                        'additional_cost' => $cost,
                        'is_active'       => (bool) ($wt['is_active'] ?? true),
                        'sort_order'      => 0,
                    ]
                );
                $tierId = $productTier->id;
            }

            \App\Models\BatchWarrantyTier::updateOrCreate(
                [
                    'stock_batch_id'    => $batch->id,
                    'variant_price_id'  => $variantId,
                    'warranty_tier_id'  => $tierId,
                ],
                [
                    'additional_cost' => (float) ($wt['additional_cost'] ?? 0),
                    'is_active'       => (bool) ($wt['is_active'] ?? true),
                ]
            );
        }

        // 3.5) Feature flags on the batch (batch-wise pricing payload present)
        $flags = [];
        if (\App\Models\BatchWholesalePrice::where('stock_batch_id', $batch->id)->exists()) {
            $flags['has_wholesale'] = true;
        }
        if (\App\Models\BatchWarrantyTier::where('stock_batch_id', $batch->id)->exists()) {
            $flags['has_sell_warranty'] = true;
        }
        if ($flags) {
            $batch->update($flags);
        }

        // 4) Purchase-item price snapshot
        \App\Models\PurchaseItemPrice::create([
            'purchase_item_id' => $purchaseItem->id,
            'variant_price_id' => $purchaseItem->variant_price_id,
            'selling_price'    => (float) ($item['selling_price'] ?? $batch->selling_price ?? 0),
            'mrp'              => isset($item['mrp']) && $item['mrp'] !== '' ? (float) $item['mrp'] : $batch->mrp,
            'wholesale_price'  => $batch->wholesale_price,
            'wholesale_tiers'  => $item['wholesale_tiers'] ?? [],
            'warranty_tiers'   => $item['warranty_tiers'] ?? [],
        ]);
    }

    /**
     * ⭐ Product / Stock panel for purchases/manage (right side).
     * Lists EVERY product added in the purchase form as an accordion row
     * (title = product). Each product shows its Stock-In batch history, and each
     * batch row has a "View" button that opens a full-detail popup.
     */
    public function pricePanel(Request $request)
    {
        $ids = (array) $request->input('products', $request->input('product', []));
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        $products = collect();
        if ($ids) {
            $order = array_flip($ids);
            $products = Product::with(['stockBatches.supplier', 'stockBatches.purchase'])
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn ($p) => $order[$p->id] ?? 9999)
                ->values();
        }

        return response()->json([
            'status' => 'success',
            'html'   => view('backEnd.purchases.partials.product-batch-panel', ['products' => $products])->render(),
        ]);
    }

    /**
     * ⭐ Purchase edit data — returns a purchase's values so the New Purchase
     *    Entry form is reused for editing (published: sell price + warranty cost only).
     */
    public function editData($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can edit purchases.');
        }

        $purchase = Purchase::findOrFail($id);

        $batches = \App\Models\StockBatch::where('purchase_id', $id)
            ->where('type', 'in')
            ->with(['product:id,name', 'variant.color', 'variant.size', 'warrantyTiers.tier', 'wholesalePrices'])
            ->get();

        $items = $purchase->items()->get()->map(function ($item) use ($batches) {
            $batch = $batches->first(function ($b) use ($item) {
                return (int) $b->product_id === (int) $item->product_id
                    && (int) ($b->variant_price_id ?: 0) === (int) ($item->variant_price_id ?: 0);
            });

            $warrantyTiers = ($batch?->warrantyTiers ?? collect())->map(function ($wt) {
                return [
                    'bwt_id'          => $wt->id,
                    'tier_id'         => $wt->warranty_tier_id,
                    'warranty_type'   => $wt->tier?->warranty_type,
                    'tier_name'       => $wt->tier?->tier_name,
                    'warranty_days'   => $wt->tier?->warranty_days,
                    'additional_cost' => (float) $wt->additional_cost,
                    'is_active'       => (bool) $wt->is_active,
                ];
            })->values();

            $wholesaleTiers = ($batch?->wholesalePrices ?? collect())->map(function ($w) {
                return [
                    'id'              => $w->id,
                    'min_quantity'    => $w->min_quantity,
                    'max_quantity'    => $w->max_quantity,
                    'wholesale_price' => (float) $w->wholesale_price,
                ];
            })->values();

            return [
                'batch_id'        => $batch?->id,
                'product_id'      => $item->product_id,
                'variant_id'      => $item->variant_price_id,
                'qty'             => (int) $item->qty,
                'unit_cost'       => (float) $item->unit_cost,
                'batch_no'        => $batch?->batch_no,
                'selling_price'   => $batch?->selling_price !== null ? (float) $batch->selling_price : null,
                'mrp'             => $batch?->mrp !== null ? (float) $batch->mrp : null,
                'serial_numbers'  => ($batch && is_array($batch->sn_stock)) ? array_values($batch->sn_stock) : [],
                'wholesale_tiers' => $wholesaleTiers,
                'warranty_tiers'  => $warrantyTiers,
            ];
        });

        return response()->json([
            'status' => 'success',
            'purchase' => [
                'id'            => $purchase->id,
                'supplier_id'   => $purchase->supplier_id,
                'invoice_no'    => $purchase->invoice_no,
                'purchase_date' => $purchase->purchase_date
                    ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d')
                    : now()->toDateString(),
                'discount'      => (float) $purchase->discount,
                'shipping_cost' => (float) $purchase->shipping_cost,
                'paid_amount'   => (float) $purchase->paid_amount,
                'note'          => $purchase->note,
            ],
            'items' => $items,
        ]);
    }

    /**
    * Update a batch's sell price from the Batch tab.
     */
    public function saveBatchPricing(Request $request)
    {
        $request->validate([
            'batch_id'       => 'required|integer',
            'selling_price'  => 'nullable|numeric|min:0',
            'pos_enabled'    => 'nullable|boolean',
        ]);

        $batch = \App\Models\StockBatch::findOrFail($request->batch_id);

        // Only touch fields that were actually sent — the POS toggle in the price
        // panel posts just pos_enabled and must not wipe selling_price.
        if ($request->has('selling_price')) {
            $batch->selling_price = $request->filled('selling_price') ? (float) $request->selling_price : null;
            $batch->is_manual_price = true;
            $batch->price_updated_at = now();
            $batch->price_updated_by = Auth::id();
        }
        if ($request->has('pos_enabled')) {
            $batch->pos_enabled = (bool) $request->boolean('pos_enabled');
        }
        $batch->save();

        app(\App\Services\PricingService::class)->refreshProductCache($batch->product);

        log_activity('pricing', 'update_batch', "Batch #{$batch->id} updated", $batch, [
            'selling_price' => $batch->selling_price,
            'pos_enabled'   => $batch->pos_enabled,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Batch updated']);
    }

    /**
     * Update a batch's in-stock serial numbers (SN) from the batch detail popup.
     * Only sn_stock (unsold units) is editable here; sold SNs are tracked separately.
     */
    public function saveBatchSerials(Request $request)
    {
        $request->validate([
            'batch_id'  => 'required|integer',
            'serials'   => 'nullable|array',
            'serials.*' => 'nullable|string|max:255',
        ]);

        $batch = \App\Models\StockBatch::findOrFail($request->batch_id);

        $serials = array_values(array_filter(array_map(
            fn ($s) => trim((string) $s),
            (array) $request->serials
        )));

        // Stock and SN count must match for serialised batches
        $remaining = (int) $batch->remaining_qty;
        if (count($serials) > 0 && count($serials) !== $remaining) {
            return response()->json([
                'status'  => 'error',
                'message' => "Batch has {$remaining} unit(s) remaining, but you entered " . count($serials) . " serial number(s). Stock and SN count must match.",
            ], 422);
        }

        // 🔁 SN must be unique per product — reject repeats within this list, and
        //    any SN already recorded (stock or sold) on another batch of the same product.
        $internalDupes = $this->findInternalDuplicates($serials);
        if ($internalDupes) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Duplicate serial number(s) entered: ' . implode(', ', $internalDupes),
            ], 422);
        }
        $existingClash = $this->findDuplicateSerialsForProduct($batch->product_id, $serials, $batch->id);
        if ($existingClash) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Serial number(s) already exist for this product: ' . implode(', ', $existingClash),
            ], 422);
        }

        $batch->sn_stock = $serials;
        $batch->save();

        log_activity('pricing', 'update_batch_serials', "Batch #{$batch->id} serial numbers updated", $batch, [
            'sn_count' => count($serials),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => count($serials) . ' serial number(s) saved',
        ]);
    }

    /**
    * Bulk-update sell price + MRP for MANY batches at once (purchase edit page).
     * Only sell prices are changed here — purchase/unit cost is never touched.
     */
    public function saveBatchesPricing(Request $request)
    {
        $request->validate([
            'batches'                 => 'required|array',
            'batches.*.batch_id'      => 'required|integer',
            'batches.*.selling_price' => 'nullable|numeric|min:0',
            'batches.*.mrp'           => 'nullable|numeric|min:0',
        ]);

        $count = 0;
        $productIds = [];
        $purchaseIds = [];

        DB::transaction(function () use ($request, &$count, &$productIds, &$purchaseIds) {
            foreach ((array) $request->batches as $row) {
                $batch = \App\Models\StockBatch::find($row['batch_id'] ?? null);
                if (!$batch) {
                    continue;
                }
                $batch->selling_price = (isset($row['selling_price']) && $row['selling_price'] !== '')
                    ? (float) $row['selling_price'] : null;
                $batch->mrp = (isset($row['mrp']) && $row['mrp'] !== '') ? (float) $row['mrp'] : null;
                $batch->is_manual_price = true;
                $batch->price_updated_at = now();
                $batch->price_updated_by = Auth::id();
                $batch->save();
                $productIds[$batch->product_id] = true;
                if ($batch->purchase_id) {
                    $purchaseIds[$batch->purchase_id] = true;
                }
                $count++;
            }
        });

        // Refresh cached catalog price for every touched product
        foreach (array_keys($productIds) as $pid) {
            $product = Product::find($pid);
            if ($product) {
                app(\App\Services\PricingService::class)->refreshProductCache($product);
            }
        }

        log_activity('pricing', 'update_batches_bulk', "Bulk sell-price update ({$count} batches)", null, [
            'count' => $count,
        ]);

        // Record an 'edit' audit log for each purchase whose batches were updated
        // (edit-mode "Update Purchase" on the manage page). Header fields are locked
        // during edit, so old == new; the description explains what changed.
        foreach (array_keys($purchaseIds) as $pid) {
            $purchase = Purchase::find($pid);
            if (!$purchase) {
                continue;
            }
            $fundBalance = $this->calculateFundBalance();
            PurchaseLog::create([
                'purchase_id'         => $purchase->id,
                'action'              => 'edit',
                'old_invoice_no'      => $purchase->invoice_no,
                'new_invoice_no'      => $purchase->invoice_no,
                'old_purchase_date'   => $purchase->purchase_date,
                'new_purchase_date'   => $purchase->purchase_date,
                'old_paid_amount'     => $purchase->paid_amount,
                'new_paid_amount'     => $purchase->paid_amount,
                'old_grand_total'     => $purchase->grand_total,
                'new_grand_total'     => $purchase->grand_total,
                'old_note'            => $purchase->note,
                'new_note'            => $purchase->note,
                'fund_balance_before' => $fundBalance,
                'fund_balance_after'  => $fundBalance,
                'description'         => "Purchase edited: Invoice '{$purchase->invoice_no}' — sell price/MRP updated on {$count} batch(es).",
                'performed_by'        => Auth::id(),
            ]);
        }

        return response()->json(['status' => 'success', 'message' => $count . ' sell price(s) updated']);
    }

    /**
     * Set a batch as the one active website batch (admin override).
     */
    public function activateWebsiteBatch(Request $request)
    {
        $request->validate(['batch_id' => 'required|integer']);
        $batch = \App\Models\StockBatch::findOrFail($request->batch_id);
        app(\App\Services\PricingService::class)->setActiveWebsiteBatch($batch->product, $batch->id);
        return response()->json(['status' => 'success', 'message' => 'Website batch activated']);
    }

    /**
     * Save per-variant prices for a batch (Variant tab).
     */
    public function saveVariantPricing(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|integer',
            'prices'   => 'required|array',
            'prices.*.variant_id' => 'required|integer',
            'prices.*.price'      => 'nullable|numeric|min:0',
            'prices.*.old_price'  => 'nullable|numeric|min:0',
            'prices.*.stock'      => 'nullable|integer|min:0',
        ]);

        foreach ($request->prices as $row) {
            \App\Models\BatchVariantPrice::updateOrCreate(
                ['stock_batch_id' => $request->batch_id, 'variant_price_id' => (int) $row['variant_id']],
                [
                    'price'     => (float) ($row['price'] ?? 0),
                    'old_price' => isset($row['old_price']) && $row['old_price'] !== '' ? (float) $row['old_price'] : null,
                    'stock'     => (int) ($row['stock'] ?? 0),
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Variant prices saved']);
    }

    /**
     * Save wholesale tiers for a batch (Wholesale tab).
     */
    public function saveWholesalePricing(Request $request)
    {
        $request->validate([
            'batch_id'   => 'required|integer',
            'tiers'      => 'nullable|array',
            'tiers.*.id' => 'nullable|integer',
            'tiers.*.variant_id'      => 'nullable|integer',
            'tiers.*.min_quantity'    => 'required|integer|min:1',
            'tiers.*.max_quantity'    => 'nullable|integer|min:1',
            'tiers.*.wholesale_price' => 'required|numeric|min:0',
            'delete_ids' => 'nullable|array',
        ]);

        $batchId = (int) $request->batch_id;

        foreach ((array) $request->delete_ids as $id) {
            \App\Models\BatchWholesalePrice::where('id', $id)->where('stock_batch_id', $batchId)->delete();
        }

        foreach ((array) $request->tiers as $t) {
            $data = [
                'variant_price_id' => !empty($t['variant_id']) ? (int) $t['variant_id'] : null,
                'min_quantity'     => (int) $t['min_quantity'],
                'max_quantity'     => !empty($t['max_quantity']) ? (int) $t['max_quantity'] : null,
                'wholesale_price'  => (float) $t['wholesale_price'],
            ];
            if (!empty($t['id'])) {
                $row = \App\Models\BatchWholesalePrice::where('id', $t['id'])->where('stock_batch_id', $batchId)->first();
                if ($row) {
                    $row->update($data);
                }
            } else {
                $data['stock_batch_id'] = $batchId;
                \App\Models\BatchWholesalePrice::create($data);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Wholesale tiers saved']);
    }

    /**
    * Save warranty tiers for a batch (Warranty tab) — cost, active flag, add/remove.
     */
    public function saveWarrantyPricing(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|integer',
            'tiers'    => 'nullable|array',
            'tiers.*.tier_id'         => 'nullable|integer',
            'tiers.*.warranty_type'   => 'nullable|string|max:50',
            'tiers.*.tier_name'       => 'nullable|string|max:255',
            'tiers.*.warranty_days'   => 'nullable|integer|min:0',
            'tiers.*.additional_cost' => 'nullable|numeric',
            'tiers.*.is_active'       => 'nullable|boolean',
            'delete_ids'              => 'nullable|array',
        ]);

        $batchId = (int) $request->batch_id;
        $batch   = \App\Models\StockBatch::findOrFail($batchId);

        foreach ((array) $request->delete_ids as $id) {
            \App\Models\BatchWarrantyTier::where('id', $id)->where('stock_batch_id', $batchId)->delete();
        }

        foreach ((array) $request->tiers as $t) {
            $tierId = (int) ($t['tier_id'] ?? 0);

            // New warranty option created directly from the purchase
            if (!$tierId) {
                $wType = $t['warranty_type'] ?? null;
                if (!$wType) {
                    continue;
                }
                $tierName = trim((string) ($t['tier_name'] ?? ''));
                if ($tierName === '') {
                    $tierName = \App\Enums\WarrantyType::tryFrom($wType)?->label()
                        ?? ucwords(str_replace('_', ' ', $wType));
                }
                $days = (int) ($t['warranty_days'] ?? 0);
                $cost = (float) ($t['additional_cost'] ?? 0);

                $productTier = \App\Models\ProductWarrantyTier::firstOrCreate(
                    ['product_id' => $batch->product_id, 'warranty_type' => $wType, 'variant_id' => null],
                    [
                        'tier_name'       => $tierName,
                        'warranty_days'   => $days,
                        'price'           => $cost,
                        'additional_cost' => $cost,
                        'is_active'       => (bool) ($t['is_active'] ?? true),
                        'sort_order'      => 0,
                    ]
                );
                $tierId = $productTier->id;
            }

            \App\Models\BatchWarrantyTier::updateOrCreate(
                [
                    'stock_batch_id'   => $batchId,
                    'variant_price_id' => null,
                    'warranty_tier_id' => $tierId,
                ],
                [
                    'additional_cost' => (float) ($t['additional_cost'] ?? 0),
                    'is_active'       => (bool) ($t['is_active'] ?? true),
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Warranty tiers saved']);
    }
}
