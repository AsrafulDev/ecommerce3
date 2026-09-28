<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderDetails;   // ✅ এইটাই এখন ইউজ হবে
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\StockBatch;

class ReportController extends Controller
{
    /**
     * Whole-period SUM over the first columns that actually exist in the table.
     * Fixes totals previously computed from the paginated (current-page) rows.
     */
    protected function sumExistingColumns($query, array $candidates): float
    {
        $existing = array_values(array_filter(
            $candidates,
            fn ($col) => Schema::hasColumn($query->getModel()->getTable(), $col)
        ));

        if (!$existing) {
            return 0.0;
        }

        // A single per-row COALESCE fallback chain: COALESCE(col1, col2, …, 0),
        // so each row contributes its first existing/non-null column — never a
        // sum of two arguments (sum(a, b) is invalid SQL on MariaDB/MySQL).
        $expr = 'COALESCE(' . implode(', ', $existing) . ', 0)';

        return (float) (clone $query)->sum(DB::raw($expr));
    }
    /**
     * Common date range helper
     * type = today / month / year / range
     */
    protected function getDateRange(Request $request)
    {
        $type   = $request->get('type', 'today'); // default: today
        $now    = Carbon::now();
        $from   = null;
        $to     = null;
        $label  = '';

        if ($type === 'today') {
            $from  = $now->copy()->startOfDay();
            $to    = $now->copy()->endOfDay();
            $label = 'Today - ' . $now->format('d M, Y');
        } elseif ($type === 'month') {
            $year  = (int) $request->get('year', $now->year);
            $month = (int) $request->get('month', $now->month);

            $from  = Carbon::create($year, $month, 1)->startOfDay();
            $to    = $from->copy()->endOfMonth();
            $label = 'Month - ' . $from->format('F Y');
        } elseif ($type === 'year') {
            $year  = (int) $request->get('year', $now->year);
            $from  = Carbon::create($year, 1, 1)->startOfDay();
            $to    = Carbon::create($year, 12, 31)->endOfDay();
            $label = 'Year - ' . $year;
        } else { // range
            $fromInput = $request->get('from_date');
            $toInput   = $request->get('to_date');

            $from = $fromInput
                ? Carbon::parse($fromInput)->startOfDay()
                : $now->copy()->startOfMonth();

            $to = $toInput
                ? Carbon::parse($toInput)->endOfDay()
                : $now->copy()->endOfDay();

            $label = 'From ' . $from->format('d M, Y') . ' To ' . $to->format('d M, Y');
        }

        return [$from, $to, $label, $type];
    }

    /**
     * অর্ডার থেকে numeric shipping amount বের করার helper
     */
    protected function resolveOrderShipping($order)
    {
        if (isset($order->shipping_amount) && is_numeric($order->shipping_amount)) {
            return (float) $order->shipping_amount;
        }

        if (isset($order->shipping_charge) && is_numeric($order->shipping_charge)) {
            return (float) $order->shipping_charge;
        }

        if (isset($order->shipping_cost) && is_numeric($order->shipping_cost)) {
            return (float) $order->shipping_cost;
        }

        if (isset($order->shipping) && is_numeric($order->shipping)) {
            return (float) $order->shipping;
        }

        return 0.0;
    }

    /**
     * অর্ডার থেকে numeric total বের করার helper
     * 👉 তোমার DB অনুসারে মূল টোটাল কলামটা `amount`
     */
    protected function resolveOrderTotal($order)
    {
        if (isset($order->amount) && is_numeric($order->amount)) {
            return (float) $order->amount;
        }

        if (isset($order->total) && is_numeric($order->total)) {
            return (float) $order->total;
        }

        if (isset($order->total_amount) && is_numeric($order->total_amount)) {
            return (float) $order->total_amount;
        }

        if (isset($order->grand_total) && is_numeric($order->grand_total)) {
            return (float) $order->grand_total;
        }

        if (isset($order->subtotal) && is_numeric($order->subtotal)) {
            return (float) $order->subtotal;
        }

        return 0.0;
    }

    /**
     * PURCHASE helper গুলো
     */
    protected function resolvePurchaseTotal($purchase)
    {
        if (isset($purchase->total) && is_numeric($purchase->total)) {
            return (float) $purchase->total;
        }
        if (isset($purchase->grand_total) && is_numeric($purchase->grand_total)) {
            return (float) $purchase->grand_total;
        }
        if (isset($purchase->total_amount) && is_numeric($purchase->total_amount)) {
            return (float) $purchase->total_amount;
        }
        if (isset($purchase->amount) && is_numeric($purchase->amount)) {
            return (float) $purchase->amount;
        }
        return 0.0;
    }

    protected function resolvePurchasePaid($purchase)
    {
        if (isset($purchase->paid) && is_numeric($purchase->paid)) {
            return (float) $purchase->paid;
        }
        if (isset($purchase->paid_amount) && is_numeric($purchase->paid_amount)) {
            return (float) $purchase->paid_amount;
        }
        if (isset($purchase->payment) && is_numeric($purchase->payment)) {
            return (float) $purchase->payment;
        }
        return 0.0;
    }

    protected function resolvePurchaseDue($purchase)
    {
        if (isset($purchase->due) && is_numeric($purchase->due)) {
            return (float) $purchase->due;
        }
        if (isset($purchase->due_amount) && is_numeric($purchase->due_amount)) {
            return (float) $purchase->due_amount;
        }
        if (isset($purchase->balance) && is_numeric($purchase->balance)) {
            return (float) $purchase->balance;
        }
        return 0.0;
    }

    /* =======================
     *  ORDER REPORT
     * ======================= */
    public function orders(Request $request)
    {
        [$from, $to, $label, $type] = $this->getDateRange($request);

$query = Order::whereBetween('created_at', [$from, $to])
    ->orderBy('created_at', 'desc');

$orders = $query->clone()->paginate(20)->withQueryString();

        // Whole-period totals (not just the current page)
        $totalOrders = (clone $query)->count();

        $totalAmount = $this->sumExistingColumns($query, [
            'amount', 'total', 'total_amount', 'grand_total', 'subtotal',
        ]);

        $totalDiscount = $this->sumExistingColumns($query, [
            'discount', 'discount_amount', 'coupon_discount',
        ]);

        $totalShipping = $this->sumExistingColumns($query, [
            'shipping_amount', 'shipping_charge', 'shipping_cost', 'shipping',
        ]);

        // CSV Export
        if ($request->get('export') === 'csv') {
            $fileName = 'order-report-' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $self = $this;

            $callback = function () use ($orders, $label, $self) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Order Report', $label]);
                fputcsv($handle, []);
                fputcsv($handle, ['Invoice', 'Customer', 'Total', 'Discount', 'Shipping', 'Status', 'Date']);

                foreach ($orders as $order) {
                    $shipping = $self->resolveOrderShipping($order);
                    $total    = $self->resolveOrderTotal($order);

                    $discount = 0.0;
                    if (isset($order->discount) && is_numeric($order->discount)) {
                        $discount = (float) $order->discount;
                    } elseif (isset($order->discount_amount) && is_numeric($order->discount_amount)) {
                        $discount = (float) $order->discount_amount;
                    } elseif (isset($order->coupon_discount) && is_numeric($order->coupon_discount)) {
                        $discount = (float) $order->coupon_discount;
                    }

                    fputcsv($handle, [
                        $order->invoice_id ?? $order->id,
                        $order->customer_name ?? ($order->customer->name ?? ''),
                        $total,
                        $discount,
                        $shipping,
                        is_object($order->status) ? ($order->status->name ?? '') : ($order->status ?? ''),
                        optional($order->created_at)->format('Y-m-d H:i'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('backEnd.reports.orders', compact(
            'orders',
            'from',
            'to',
            'label',
            'type',
            'totalOrders',
            'totalAmount',
            'totalDiscount',
            'totalShipping'
        ));
    }

    /* =======================
     *  PURCHASE REPORT
     * ======================= */
    public function purchases(Request $request)
    {
        [$from, $to, $label, $type] = $this->getDateRange($request);

        $query = Purchase::query();

        // purchase_date থাকলে সেটা ব্যবহার, না থাকলে created_at
        if (Schema::hasColumn('purchases', 'purchase_date')) {
            $query->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()]);
        } else {
            $query->whereBetween('created_at', [$from, $to]);
        }

 $purchases = $query->clone()
                   ->orderBy('id', 'desc')
                   ->paginate(20)
                   ->withQueryString();
        // Whole-period summary (not just the current page)
        $totalPurchaseAmount = $this->sumExistingColumns($query, [
            'total', 'grand_total', 'total_amount', 'amount',
        ]);

        $totalPaid = $this->sumExistingColumns($query, [
            'paid', 'paid_amount', 'payment',
        ]);

        $totalDue = $this->sumExistingColumns($query, [
            'due', 'due_amount', 'balance',
        ]);

        // Blade-এ দেখানোর জন্য ইন-মেমরি ভ্যালু সেট করে দিচ্ছি
        foreach ($purchases as $p) {
            $p->total = $this->resolvePurchaseTotal($p);
            $p->paid  = $this->resolvePurchasePaid($p);
            $p->due   = $this->resolvePurchaseDue($p);
        }

        // CSV Export
        if ($request->get('export') === 'csv') {
            $fileName = 'purchase-report-' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function () use ($purchases, $label) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Purchase Report', $label]);
                fputcsv($handle, []);
                fputcsv($handle, ['Invoice', 'Supplier', 'Total', 'Paid', 'Due', 'Date']);

                foreach ($purchases as $p) {
                    $dateValue = $p->purchase_date ?? $p->created_at;
                    $dateStr   = $dateValue ? Carbon::parse($dateValue)->format('Y-m-d') : '';

                    fputcsv($handle, [
                        $p->invoice_no ?? $p->id,
                        $p->supplier->name ?? '',
                        $p->total ?? 0,
                        $p->paid ?? 0,
                        $p->due ?? 0,
                        $dateStr,
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('backEnd.reports.purchases', compact(
            'purchases',
            'from',
            'to',
            'label',
            'type',
            'totalPurchaseAmount',
            'totalPaid',
            'totalDue'
        ));
    }

    /* =======================
     *  EXPENSE REPORT
     * ======================= */
    public function expenses(Request $request)
    {
        [$from, $to, $label, $type] = $this->getDateRange($request);

        $query = Expense::query();

        if (Schema::hasColumn('expenses', 'expense_date')) {
            $query->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        } else {
            $query->whereBetween('created_at', [$from, $to]);
        }

$expenses = $query->clone()
                  ->orderBy('id', 'desc')
                  ->paginate(20)
                  ->withQueryString();

$totalExpense = $this->sumExistingColumns($query, ['amount']);


        if ($request->get('export') === 'csv') {
            $fileName = 'expense-report-' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function () use ($expenses, $label) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Expense Report', $label]);
                fputcsv($handle, []);
                fputcsv($handle, ['Date', 'Title', 'Category', 'Amount', 'Note']);

                foreach ($expenses as $e) {
                    $dateValue = $e->expense_date ?? $e->created_at;
                    $dateStr   = $dateValue ? Carbon::parse($dateValue)->format('Y-m-d') : '';

                    fputcsv($handle, [
                        $dateStr,
                        $e->title,
                        $e->category,
                        $e->amount,
                        $e->note,
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('backEnd.reports.expenses', compact(
            'expenses',
            'from',
            'to',
            'label',
            'type',
            'totalExpense'
        ));
    }

    /* =======================
     *  STOCK REPORT (LIVE STOCK)
     * ======================= */
    public function stock(Request $request)
    {
        $products = Product::orderBy('name')
                   ->paginate(20)
                   ->withQueryString();

        // Whole-period totals, plus the authoritative batch-based valuation
        // (source of truth = stock_batches.remaining_qty * unit_cost, per AGENTS.md;
        //  products.stock is a denormalized copy that can drift).
        $totalStockQty   = (float) Product::sum('stock');
        $totalStockValue = (float) Product::sum(DB::raw('COALESCE(purchase_price, 0) * COALESCE(stock, 0)'));

        $batchStockQuery = StockBatch::where('type', 'in')->where('remaining_qty', '>', 0);
        $batchQty   = (float) (clone $batchStockQuery)->sum('remaining_qty');
        $batchValue = (float) (clone $batchStockQuery)->sum(DB::raw('remaining_qty * unit_cost'));

        if ($request->get('export') === 'csv') {
            $fileName = 'stock-report-' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function () use ($products) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Stock Report - Live']);
                fputcsv($handle, []);
                fputcsv($handle, ['Product', 'SKU', 'Stock', 'Purchase Price', 'Sale Price', 'Stock Value']);

                foreach ($products as $p) {
                    $purchasePrice = $p->purchase_price ?? 0;
                    $salePrice     = $p->new_price ?? $p->old_price ?? 0;
                    $stock         = $p->stock ?? 0;
                    $stockValue    = $purchasePrice * $stock;

                    fputcsv($handle, [
                        $p->name,
                        $p->sku ?? '',
                        $stock,
                        $purchasePrice,
                        $salePrice,
                        $stockValue,
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('backEnd.reports.stock', compact(
            'products',
            'totalStockQty',
            'totalStockValue',
            'batchQty',
            'batchValue'
        ));
    }

    /* =======================
     *  BASIC PROFIT SUMMARY (Lite / operational P&L)
     *
     *  Sale recognition: delivered/completed orders (order_status).
     *  COGS: batch-realized order_details.cogs, snapshot price only as fallback
     *  (same rule as the Accounts dashboard — one operational truth).
     *  Cash movement (collections, supplier payments, owner capital/withdrawal)
     *  is deliberately NOT part of profit.
     * ======================= */
    public function profitLoss(Request $request)
    {
        [$from, $to, $label, $type] = $this->getDateRange($request);

        // 1) SALES — only recognized orders; cancelled/pending/returned are not sales
        $orders = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('order_status', ['delivered', 'completed'])
            ->get();

        $salesAmount = $orders->sum(function ($order) {
            return $this->resolveOrderTotal($order);
        });

        // 2) REFUNDS (contra revenue) — money paid back for refunded orders
        $refunds = (float) FundTransaction::where('direction', 'out')
            ->whereIn('source', ['refund', 'order_refund'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // 3) COGS — prefer batch-realized cogs stored on order_details
        $orderDetails = OrderDetails::whereIn('order_id', $orders->pluck('id'))
            ->with('product:id,purchase_price') // ✅ Eager load to avoid N+1
            ->get(); // ✅ এখানে plural মডেল

        $cogs = 0;
        foreach ($orderDetails as $od) {
            if ($od->cogs !== null && (float) $od->cogs > 0) {
                $cogs += (float) $od->cogs;
                continue;
            }

            $purchasePrice = $od->purchase_price ?? ($od->product->purchase_price ?? 0);
            $cogs += $purchasePrice * ($od->qty ?? 0);
        }

        // 4) OTHER INCOME — operational income rows (warranty charges/resale).
        //    Owner capital ('manual_add') is Cash In, NOT income → excluded.
        $otherIncome = (float) FundTransaction::where('direction', 'in')
            ->whereIn('source', ['warranty', 'warranty_resell'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // 5) EXPENSES — Expense table + salaries/bonuses (which live in fund rows,
        //    not the Expense table, and are genuine operating expenses)
        $expQuery = Expense::query();
        if (Schema::hasColumn('expenses', 'expense_date')) {
            $expQuery->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        } else {
            $expQuery->whereBetween('created_at', [$from, $to]);
        }

        $operatingExpense = (float) $expQuery->sum('amount');

        $salaryBonus = (float) FundTransaction::where('direction', 'out')
            ->whereIn('source', ['employee_salary', 'employee_bonus'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $totalExpense = $operatingExpense + $salaryBonus;

        // 6) GROSS & NET
        $netSales    = $salesAmount - $refunds;
        $grossProfit = $netSales - $cogs;
        $netProfit   = $grossProfit + $otherIncome - $totalExpense;

        if ($request->get('export') === 'csv') {
            $fileName = 'profit-loss-' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function () use ($label, $salesAmount, $refunds, $netSales, $cogs, $grossProfit, $otherIncome, $operatingExpense, $salaryBonus, $totalExpense, $netProfit) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Basic Profit Summary', $label]);
                fputcsv($handle, []);
                fputcsv($handle, ['Sales (delivered/completed)', $salesAmount]);
                fputcsv($handle, ['Refunds', -$refunds]);
                fputcsv($handle, ['Net Sales', $netSales]);
                fputcsv($handle, ['COGS (batch-realized)', $cogs]);
                fputcsv($handle, ['Gross Profit', $grossProfit]);
                fputcsv($handle, ['Other Income', $otherIncome]);
                fputcsv($handle, ['Expenses', $operatingExpense]);
                fputcsv($handle, ['Salaries & Bonuses', $salaryBonus]);
                fputcsv($handle, ['Total Expenses', $totalExpense]);
                fputcsv($handle, ['Net Profit', $netProfit]);
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('backEnd.reports.profit_loss', compact(
            'from',
            'to',
            'label',
            'type',
            'salesAmount',
            'refunds',
            'netSales',
            'cogs',
            'otherIncome',
            'operatingExpense',
            'salaryBonus',
            'totalExpense',
            'grossProfit',
            'netProfit'
        ));
    }
}
