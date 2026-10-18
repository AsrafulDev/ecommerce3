<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\CustomerAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LiteLedgerController extends Controller
{
    private const NON_RECEIVABLE_STATUSES = ['cancelled', 'returned', 'return_approved', 'closed'];

    public function __construct()
    {
        // Balance pages are read-only views of the funds ledger; collecting money is a write.
        $this->middleware('permission:fund-list|fund-create|fund-edit', ['only' => ['customers', 'customer', 'customerDue', 'suppliers', 'supplier']]);
        $this->middleware('permission:fund-create', ['only' => ['collectCustomerDue']]);
    }

    public function customers()
    {
        $activeOrders = fn ($query) => $query->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES);
        $customers = Customer::query()
            ->withSum(['orders' => $activeOrders], 'amount')
            ->withSum(['orders' => $activeOrders], 'paid_amount')
            ->withSum(['orders' => $activeOrders], 'due_amount')
            ->withMax(['orders' => $activeOrders], 'created_at')
            ->orderBy('name')->paginate(25);
        return view('backEnd.accounts.customer-ledger-index', compact('customers'));
    }

    public function customer(int $id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()
            ->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)
            ->with('paymentHistory')
            ->orderBy('created_at')->get();
        $rows = collect();
        foreach ($orders as $order) {
            $rows->push(['date' => $order->created_at, 'label' => 'Sale', 'reference' => $order->invoice_id ?: '#'.$order->id, 'charge' => (float) $order->amount, 'payment' => 0]);
            foreach ($order->paymentHistory as $payment) {
                if ((float) $payment->adjustment_amount > 0) {
                    // Discount, VAT or AIT: reduces what is owed, no cash moved.
                    $rows->push(['date' => $payment->created_at, 'label' => ucfirst((string) $payment->adjustment_type).' adjustment', 'reference' => $payment->trx_note ?: '#'.$payment->id, 'charge' => 0, 'payment' => (float) $payment->adjustment_amount]);
                    continue;
                }
                $rows->push(['date' => $payment->created_at, 'label' => 'Customer Payment', 'reference' => $payment->trx_note ?: '#'.$payment->id, 'charge' => 0, 'payment' => (float) $payment->amount]);
            }
        }
        return view('backEnd.accounts.customer-ledger', ['customer' => $customer, 'rows' => $this->running($rows), 'due' => (float) $orders->sum('due_amount')]);
    }

    public function customerDue(int $id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()->where('due_amount', '>', 0)->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)->orderBy('created_at')->get();
        $advance = (float) CustomerAdvance::where('customer_id', $id)->sum('amount');
        return view('backEnd.accounts.customer-due', compact('customer', 'orders', 'advance'));
    }

    public function collectCustomerDue(Request $request, int $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01', 'payment_method' => 'nullable|string|max:55', 'trx_note' => 'nullable|string|max:255', 'order_ids' => 'nullable|array', 'order_ids.*' => 'integer|exists:orders,id']);
        $customer = Customer::findOrFail($id);
        $remaining = round((float) $request->amount, 2);
        $ids = collect($request->input('order_ids', []))->map(fn ($v) => (int) $v)->values();
        $orders = $customer->orders()->where('due_amount', '>', 0)->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)
            ->when($ids->isNotEmpty(), fn ($q) => $q->orderByRaw('FIELD(id, '.$ids->implode(',').')'), fn ($q) => $q->orderBy('created_at'))->get();
        $service = app(\App\Services\PaymentCollectionService::class);
        foreach ($orders as $order) {
            if ($remaining <= 0) break;
            $payment = $service->collect($order, min($remaining, (float) $order->due_amount), $request->input('payment_method', 'Cash'), $request->input('trx_note'), auth()->id());
            if ($payment) $remaining = round($remaining - (float) $payment->amount, 2);
        }
        if ($remaining > 0) CustomerAdvance::create(['customer_id' => $customer->id, 'amount' => $remaining, 'payment_method' => $request->input('payment_method', 'Cash'), 'trx_note' => $request->input('trx_note'), 'created_by' => auth()->id()]);
        return back()->with('success', 'Payment allocated across bills successfully.'.($remaining > 0 ? ' Extra amount saved as customer advance.' : ''));
    }

    public function suppliers()
    {
        $suppliers = Supplier::query()->withSum('purchases', 'grand_total')->withSum('purchases', 'paid_amount')
            ->withSum('purchases', 'due_amount')->withMax('purchases', 'purchase_date')->orderBy('name')->paginate(25);
        return view('backEnd.accounts.supplier-ledger-index', compact('suppliers'));
    }

    public function supplier(int $id)
    {
        $supplier = Supplier::findOrFail($id);
        $purchases = $supplier->purchases()->with('payments')->orderBy('purchase_date')->get();
        $rows = collect();
        foreach ($purchases as $purchase) {
            $rows->push(['date' => $purchase->purchase_date, 'label' => 'Purchase', 'reference' => $purchase->invoice_no ?: '#'.$purchase->id, 'charge' => (float) $purchase->grand_total, 'payment' => 0]);
            foreach ($purchase->payments as $payment) {
                $rows->push(['date' => $payment->payment_date ?: $payment->created_at, 'label' => 'Supplier Payment', 'reference' => '#'.$payment->id, 'charge' => 0, 'payment' => (float) $payment->amount]);
            }
        }
        return view('backEnd.accounts.supplier-ledger', ['supplier' => $supplier, 'rows' => $this->running($rows), 'due' => (float) $purchases->sum('due_amount')]);
    }

    private function running(Collection $rows): Collection
    {
        $balance = 0;
        return $rows->sortBy('date')->values()->map(function (array $row) use (&$balance) {
            $balance += $row['charge'] - $row['payment'];
            return $row + ['balance' => round($balance, 2)];
        });
    }
}
