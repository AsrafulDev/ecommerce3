<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FundHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\PaymentCollectionService;
use App\Services\SupplierPaymentReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Receipt and payment entry: one party, many invoices, one save.
 *
 * Money is never written here directly. Each receipt goes through
 * PaymentCollectionService::collect() and each supplier payment through the same
 * recordTrace() + reconcilePayment() path the purchase screen uses, so the order,
 * purchase and fund figures stay in step with every other screen.
 */
class AccountsEntryController extends Controller
{
    /** The same choices the order collect-due dialog offers, so the two screens record alike. */
    public const RECEIPT_METHODS = ['Cash', 'Card', 'Bank', 'MFS'];

    private const NON_RECEIVABLE_STATUSES = ['cancelled', 'returned', 'return_approved', 'closed'];

    public function __construct()
    {
        $this->middleware('permission:fund-create');
    }

    public function receiptCreate(Request $request)
    {
        $customer = $request->filled('customer_id') ? Customer::find($request->integer('customer_id')) : null;

        return view('backEnd.accounts.entry.receipt', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'customer' => $customer,
            'orders' => $customer ? $this->openOrders($customer->id) : collect(),
            'methods' => self::RECEIPT_METHODS,
        ]);
    }

    public function receiptStore(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'paid_on' => ['nullable', 'date'],
            'method' => ['required', 'string', 'in:'.implode(',', self::RECEIPT_METHODS)],
            'note' => ['nullable', 'string', 'max:255'],
            'amounts' => ['nullable', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['nullable', 'numeric', 'min:0'],
            'vats' => ['nullable', 'array'],
            'vats.*' => ['nullable', 'numeric', 'min:0'],
            'aits' => ['nullable', 'array'],
            'aits.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cash = $this->positiveLines($data['amounts'] ?? []);
        $adjustments = [
            'discount' => $this->positiveLines($data['discounts'] ?? []),
            'vat' => $this->positiveLines($data['vats'] ?? []),
            'ait' => $this->positiveLines($data['aits'] ?? []),
        ];

        $orderIds = array_values(array_unique(array_merge(
            array_keys($cash),
            array_keys($adjustments['discount']),
            array_keys($adjustments['vat']),
            array_keys($adjustments['ait']),
        )));
        if (empty($orderIds)) {
            return back()->withInput()->with('error', 'Enter an amount or an adjustment against at least one invoice.');
        }

        // Every invoice is checked against its CURRENT due before anything is
        // booked, so a stale form cannot overpay an invoice.
        $orders = Order::whereIn('id', $orderIds)
            ->where('customer_id', $data['customer_id'])
            ->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)
            ->get()
            ->keyBy('id');

        foreach ($orderIds as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) {
                return back()->withInput()->with('error', "Invoice #{$orderId} does not belong to this customer or is closed.");
            }

            $total = ($cash[$orderId] ?? 0)
                + ($adjustments['discount'][$orderId] ?? 0)
                + ($adjustments['vat'][$orderId] ?? 0)
                + ($adjustments['ait'][$orderId] ?? 0);

            if ($total > (float) $order->due_amount) {
                $reference = $order->invoice_id ?: '#'.$order->id;

                return back()->withInput()->with('error', "Cash and adjustments for invoice {$reference} total ".number_format($total, 2).', more than its due of '.number_format((float) $order->due_amount, 2).'.');
            }
        }

        $collected = 0.0;
        $booked = 0;
        $collector = app(PaymentCollectionService::class);

        foreach ($cash as $orderId => $amount) {
            $payment = $collector->collect($orders->get($orderId), $amount, $data['method'], null, Auth::id(), $data['note'] ?? null, ($data['paid_on'] ?? null));
            if ($payment) {
                $collected += (float) $payment->amount;
                $booked++;
            }
        }

        $adjusted = 0.0;
        foreach ($adjustments as $type => $lines) {
            foreach ($lines as $orderId => $amount) {
                $row = $collector->adjust($orders->get($orderId), $amount, $type, Auth::id(), $data['note'] ?? null, ($data['paid_on'] ?? null));
                if ($row) {
                    $adjusted += (float) $row->adjustment_amount;
                }
            }
        }

        log_activity('accounts', 'create', 'Receipt entry: '.number_format($collected, 2).' cash, '.number_format($adjusted, 2).' discount/VAT/AIT from customer #'.$data['customer_id'], null, ['cash' => $cash, 'adjustments' => $adjustments]);

        return redirect()->route('admin.accounts.entry.receipt', ['customer_id' => $data['customer_id']])
            ->with('success', 'Received '.number_format($collected, 2).' cash and '.number_format($adjusted, 2).' of discount/VAT/AIT across '.count($orderIds).' invoices.');
    }

    public function paymentCreate(Request $request)
    {
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->integer('supplier_id')) : null;

        return view('backEnd.accounts.entry.payment', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'phone']),
            'supplier' => $supplier,
            'purchases' => $supplier ? $this->openPurchases($supplier->id) : collect(),
            'fundBalance' => FundHelper::balance(),
        ]);
    }

    public function paymentStore(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = $this->positiveLines($data['amounts']);
        if (empty($lines)) {
            return back()->withInput()->with('error', 'Enter an amount against at least one purchase.');
        }

        $purchases = Purchase::whereIn('id', array_keys($lines))
            ->where('supplier_id', $data['supplier_id'])
            ->get()
            ->keyBy('id');

        $total = 0.0;
        foreach ($lines as $purchaseId => $amount) {
            $purchase = $purchases->get($purchaseId);
            if (!$purchase) {
                return back()->withInput()->with('error', "Purchase #{$purchaseId} does not belong to this supplier.");
            }
            if ($amount > (float) $purchase->due_amount) {
                return back()->withInput()->with('error', "Amount for {$purchase->invoice_no} is more than its due of ".number_format((float) $purchase->due_amount, 2).'.');
            }
            $total += $amount;
        }

        // Same rule as the purchase screen: the fund must hold the whole batch.
        // The check and the writes run under the till lock.
        $payments = FundHelper::debitIfCovered($total, fn () => DB::transaction(function () use ($lines, $purchases, $data) {
            $reconciler = app(SupplierPaymentReconciliationService::class);

            return collect($lines)->map(function (float $amount, int $purchaseId) use ($purchases, $data, $reconciler) {
                $payment = $reconciler->recordTrace($purchases->get($purchaseId), $amount, $data['payment_date'], $data['note'] ?? null, Auth::id());
                $reconciler->reconcilePayment($payment);

                return $payment;
            })->values();
        }));

        if ($payments === null) {
            return back()->withInput()->with('error', 'Not enough balance in fund for this payment.');
        }

        // Advanced accounting is told after the money is committed, as on the purchase screen.
        $gateway = app(AdvancedAccountingGateway::class);
        foreach ($payments as $payment) {
            $gateway->supplierPaymentMade($payment);
        }

        log_activity('accounts', 'create', 'Supplier payment entry: '.number_format($total, 2).' to supplier #'.$data['supplier_id'].' across '.$payments->count().' purchases', null, ['lines' => $lines]);

        return redirect()->route('admin.accounts.entry.payment', ['supplier_id' => $data['supplier_id']])
            ->with('success', 'Paid '.number_format($total, 2).' across '.$payments->count().' purchases.');
    }

    /** @return array<int, float> purchase/order id => amount, zero and blank lines dropped */
    protected function positiveLines(array $amounts): array
    {
        return collect($amounts)
            ->map(fn ($v) => round((float) $v, 2))
            ->filter(fn (float $v, $key) => $v > 0 && ctype_digit((string) $key))
            ->mapWithKeys(fn (float $v, $key) => [(int) $key => $v])
            ->all();
    }

    protected function openOrders(int $customerId)
    {
        return Order::where('customer_id', $customerId)
            ->where('due_amount', '>', 0)
            ->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)
            ->orderBy('created_at')
            ->get(['id', 'invoice_id', 'created_at', 'amount', 'due_amount']);
    }

    protected function openPurchases(int $supplierId)
    {
        return Purchase::where('supplier_id', $supplierId)
            ->where('due_amount', '>', 0)
            ->orderBy('purchase_date')
            ->get(['id', 'invoice_no', 'purchase_date', 'grand_total', 'due_amount']);
    }
}
