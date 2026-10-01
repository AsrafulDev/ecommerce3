<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Collection;

class LiteLedgerController extends Controller
{
    public function customers()
    {
        $customers = Customer::query()->withSum('orders', 'amount')->withSum('orders', 'paid_amount')
            ->withSum('orders', 'due_amount')->withMax('orders', 'created_at')->orderBy('name')->paginate(25);
        return view('backEnd.accounts.customer-ledger-index', compact('customers'));
    }

    public function customer(int $id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()->with('paymentHistory')->orderBy('created_at')->get();
        $rows = collect();
        foreach ($orders as $order) {
            $rows->push(['date' => $order->created_at, 'label' => 'Sale', 'reference' => $order->invoice_id ?: '#'.$order->id, 'charge' => (float) $order->amount, 'payment' => 0]);
            foreach ($order->paymentHistory as $payment) {
                $rows->push(['date' => $payment->created_at, 'label' => 'Customer Payment', 'reference' => $payment->trx_note ?: '#'.$payment->id, 'charge' => 0, 'payment' => (float) $payment->amount]);
            }
        }
        return view('backEnd.accounts.customer-ledger', ['customer' => $customer, 'rows' => $this->running($rows), 'due' => (float) $orders->sum('due_amount')]);
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
