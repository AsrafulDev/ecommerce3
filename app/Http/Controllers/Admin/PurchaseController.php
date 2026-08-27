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

        $purchases = $query->paginate(10);

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
            'variantsJson'
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
            'items.*.warranty_tiers.*.additional_cost' => 'nullable|numeric|min:0',
            'items.*.warranty_tiers.*.is_active'       => 'nullable|boolean',
            'discount'      => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount'   => 'nullable|numeric|min:0',
        ]);

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
            ]);

            // ⭐ Batch-wise pricing payload (variant / wholesale / warranty / activation)
            $this->persistBatchPricing($purchaseItem, $batch, $item);
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

        return back()->with('success','Purchase created & stock updated!');
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

        // Decrement from batches FIFO (oldest with remaining stock first)
        $remaining = $qty;
        $batches = \App\Models\StockBatch::where('product_id', $product->id)
            ->where('remaining_qty', '>', 0)
            ->orderBy('created_at')
            ->get();
        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (int) $batch->remaining_qty);
            $batch->decrement('remaining_qty', $take);
            $remaining -= $take;
        }

        $product->decrement('stock', $qty);

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
     * Edit Purchase (Admin only)
     */
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can edit purchases.');
        }

        $purchase = Purchase::with(['supplier', 'items.product', 'items.variant', 'payments'])->findOrFail($id);
        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        // ⭐ Batch-wise pricing engine — batches created by this purchase
        $batches = \App\Models\StockBatch::where('purchase_id', $id)
            ->with('product:id,name', 'supplier:id,name', 'variantPrices')
            ->orderByDesc('created_at')
            ->get();

        return view('backEnd.purchases.edit', compact('purchase', 'suppliers', 'products', 'batches'));
    }

    /**
     * Update Purchase (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can update purchases.');
        }

        $request->validate([
            'invoice_no'    => 'required|string|max:50',
            'purchase_date' => 'required|date',
            'paid_amount'   => 'nullable|numeric|min:0',
            'note'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $purchase = Purchase::findOrFail($id);

            // Save old values for logging
            $old_invoice_no = $purchase->invoice_no;
            $old_purchase_date = $purchase->purchase_date;
            $old_paid_amount = $purchase->paid_amount;
            $old_grand_total = $purchase->grand_total;
            $old_note = $purchase->note;

            // Calculate fund balance before update
            $fund_balance_before = $this->calculateFundBalance();

            // Update purchase
            $new_paid_amount = (float) ($request->paid_amount ?? 0);
            $paid_diff = $new_paid_amount - $old_paid_amount;

            $purchase->update([
                'invoice_no'    => $request->invoice_no,
                'purchase_date' => $request->purchase_date,
                'paid_amount'   => $new_paid_amount,
                'due_amount'    => max(0, $purchase->grand_total - $new_paid_amount),
                'note'          => $request->note ?? null,
            ]);

            // Update linked fund transactions if paid amount changed
            if ($paid_diff != 0) {
                // Get all supplier payments for this purchase
                $payments = SupplierPayment::where('purchase_id', $purchase->id)->get();
                $total_paid_via_fund = $payments->sum('amount');

                if ($paid_diff > 0) {
                    // Paid amount increased - need to create new fund transaction
                    $fund = FundTransaction::create([
                        'direction'  => 'out',
                        'source'     => 'supplier_payment',
                        'source_id'  => null,
                        'amount'     => $paid_diff,
                        'note'       => 'Purchase payment update: '.$purchase->invoice_no,
                        'created_by' => Auth::id(),
                    ]);

                    $payment = SupplierPayment::create([
                        'supplier_id'        => $purchase->supplier_id,
                        'purchase_id'        => $purchase->id,
                        'amount'             => $paid_diff,
                        'payment_date'       => $request->purchase_date,
                        'method'             => 'fund',
                        'note'               => 'Payment adjustment',
                        'fund_transaction_id'=> $fund->id,
                        'created_by'         => Auth::id(),
                    ]);

                    $fund->source_id = $payment->id;
                    $fund->save();
                } else {
                    // Paid amount decreased - need to delete/update fund transactions
                    $amount_to_reduce = abs($paid_diff);
                    foreach ($payments as $payment) {
                        if ($amount_to_reduce <= 0) break;
                        
                        if ($payment->amount <= $amount_to_reduce) {
                            // Delete entire payment
                            if ($payment->fund_transaction_id) {
                                $fund = FundTransaction::find($payment->fund_transaction_id);
                                if ($fund) {
                                    $fund->delete();
                                }
                            }
                            $amount_to_reduce -= $payment->amount;
                            $payment->delete();
                        } else {
                            // Reduce payment amount
                            $payment->amount -= $amount_to_reduce;
                            $payment->save();
                            
                            if ($payment->fund_transaction_id) {
                                $fund = FundTransaction::find($payment->fund_transaction_id);
                                if ($fund) {
                                    $fund->amount -= $amount_to_reduce;
                                    $fund->save();
                                }
                            }
                            $amount_to_reduce = 0;
                        }
                    }
                }
            }

            // Update supplier due
            $supplier = $purchase->supplier;
            $supplier->current_due = Purchase::where('supplier_id', $supplier->id)->sum('due_amount');
            $supplier->save();

            // Calculate fund balance after update
            $fund_balance_after = $this->calculateFundBalance();

            // Create log entry
            $description = $this->generateEditDescription(
                $old_invoice_no, $old_purchase_date, $old_paid_amount, $old_grand_total, $old_note,
                $request->invoice_no, $request->purchase_date, $new_paid_amount, $purchase->grand_total, $request->note ?? null,
                $fund_balance_before, $fund_balance_after
            );

            PurchaseLog::create([
                'purchase_id' => $purchase->id,
                'action' => 'edit',
                'old_invoice_no' => $old_invoice_no,
                'new_invoice_no' => $request->invoice_no,
                'old_purchase_date' => $old_purchase_date,
                'new_purchase_date' => $request->purchase_date,
                'old_paid_amount' => $old_paid_amount,
                'new_paid_amount' => $new_paid_amount,
                'old_grand_total' => $old_grand_total,
                'new_grand_total' => $purchase->grand_total,
                'old_note' => $old_note,
                'new_note' => $request->note ?? null,
                'fund_balance_before' => $fund_balance_before,
                'fund_balance_after' => $fund_balance_after,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                             ->with('success', 'Purchase updated successfully! Fund balance adjusted automatically.');
        });
    }

    /**
     * Generate description for edit log
     */
    private function generateEditDescription($old_inv, $old_date, $old_paid, $old_total, $old_note,
                                             $new_inv, $new_date, $new_paid, $new_total, $new_note,
                                             $bal_before, $bal_after)
    {
        $parts = [];
        
        if ($old_inv != $new_inv) {
            $parts[] = "Invoice changed from '{$old_inv}' to '{$new_inv}'";
        }
        
        if ($old_date != $new_date) {
            $parts[] = "Date changed from {$old_date} to {$new_date}";
        }
        
        if ($old_paid != $new_paid) {
            $diff = $new_paid - $old_paid;
            $diff_sign = ($diff > 0) ? '+' : '';
            $parts[] = "Paid amount changed from {$old_paid} to {$new_paid} ({$diff_sign}{$diff})";
        }
        
        $balance_diff = $bal_after - $bal_before;
        $balance_sign = ($balance_diff > 0) ? '+' : '';
        $parts[] = "Fund balance changed from {$bal_before} to {$bal_after} ({$balance_sign}{$balance_diff})";
        
        return implode('. ', $parts);
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

            // Reverse stock updates
            foreach ($purchase->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->stock = max(0, $product->stock - ($item->qty - $item->returned_qty));
                    $product->save();
                }

                if ($item->variant) {
                    $item->variant->stock = max(0, $item->variant->stock - ($item->qty - $item->returned_qty));
                    $item->variant->save();
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
        $total_edits = PurchaseLog::where('action', 'edit')->count();
        $total_deletes = PurchaseLog::where('action', 'delete')->count();

        return view('backEnd.purchases.logs', compact('logs', 'total_edits', 'total_deletes'));
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
        if (!(bool) config('pricing.batch_wise', false)) {
            return;
        }

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

        // 5) Activate as the website batch if requested
        if (!empty($item['activate_website'])) {
            app(\App\Services\PricingService::class)->setActiveWebsiteBatch($batch->product, $batch->id);
        }
    }

    /**
     * ⭐ Batch-wise pricing engine — right-panel accordion on purchases/manage.
     * Renders the 4-tab panel (Batch → Variant → Wholesale → Warranty) for a product.
     */
    public function pricePanel(Request $request)
    {
        $product = Product::with([
            'stockBatches.supplier',
            'stockBatches.purchase',
            'stockBatches.variantPrices',
            'stockBatches.wholesalePrices',
            'stockBatches.warrantyTiers.tier',
            'variantPrices.color',
            'variantPrices.size',
            'wholesalePrices',
            'warrantyTiers',
        ])->findOrFail($request->product);

        return response()->json([
            'status' => 'success',
            'html'   => view('backEnd.purchases.partials.price-panel', ['product' => $product])->render(),
        ]);
    }

    /**
     * Update a batch's base sell price / MRP / flags from the Batch tab.
     */
    public function saveBatchPricing(Request $request)
    {
        $request->validate([
            'batch_id'       => 'required|integer',
            'selling_price'  => 'nullable|numeric|min:0',
            'mrp'            => 'nullable|numeric|min:0',
            'pos_enabled'    => 'nullable|boolean',
            'auto_advance'   => 'nullable|boolean',
        ]);

        $batch = \App\Models\StockBatch::findOrFail($request->batch_id);
        $batch->selling_price = $request->filled('selling_price') ? (float) $request->selling_price : null;
        $batch->mrp           = $request->filled('mrp') ? (float) $request->mrp : null;
        $batch->pos_enabled   = (bool) $request->boolean('pos_enabled');
        $batch->auto_advance  = (bool) $request->boolean('auto_advance');
        $batch->is_manual_price = true;
        $batch->price_updated_at = now();
        $batch->price_updated_by = Auth::id();
        $batch->save();

        app(\App\Services\PricingService::class)->refreshProductCache($batch->product);

        log_activity('pricing', 'update_batch', "Batch #{$batch->id} price updated", $batch, [
            'selling_price' => $batch->selling_price,
            'mrp'           => $batch->mrp,
            'pos_enabled'   => $batch->pos_enabled,
            'auto_advance'  => $batch->auto_advance,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Batch price updated']);
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
     * Save warranty tier overrides for a batch (Warranty tab).
     */
    public function saveWarrantyPricing(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|integer',
            'tiers'    => 'nullable|array',
            'tiers.*.tier_id'         => 'required|integer',
            'tiers.*.variant_id'      => 'nullable|integer',
            'tiers.*.additional_cost' => 'nullable|numeric|min:0',
            'tiers.*.is_active'       => 'nullable|boolean',
            'delete_ids' => 'nullable|array',
        ]);

        $batchId = (int) $request->batch_id;

        foreach ((array) $request->delete_ids as $id) {
            \App\Models\BatchWarrantyTier::where('id', $id)->where('stock_batch_id', $batchId)->delete();
        }

        foreach ((array) $request->tiers as $t) {
            \App\Models\BatchWarrantyTier::updateOrCreate(
                [
                    'stock_batch_id'   => $batchId,
                    'variant_price_id' => !empty($t['variant_id']) ? (int) $t['variant_id'] : null,
                    'warranty_tier_id' => (int) $t['tier_id'],
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
