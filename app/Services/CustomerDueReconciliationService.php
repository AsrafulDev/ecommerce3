<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;

/** Rebuilds customer payment state from the immutable payment-history ledger. */
class CustomerDueReconciliationService
{
    public function reconcile(Order $order): Order
    {
        $paid = round((float) $order->paymentHistory()->sum('amount'), 2);
        $order->paid_amount = $paid;
        $order->due_amount = max(0, round((float) $order->amount - $paid, 2));
        $order->payment_status = $order->due_amount > 0
            ? ($paid > 0 ? 'partial' : 'pending')
            : 'paid';
        $order->save();

        $latest = $order->paymentHistory()->latest('id')->first();
        $row = Payment::where('order_id', $order->id)->first();
        if ($latest || $row) {
            $row ??= new Payment(['order_id' => $order->id]);
            $row->customer_id = $order->customer_id;
            $row->amount = $order->paid_amount;
            $row->payment_status = $order->payment_status;
            if ($latest && !$row->payment_method) {
                $row->payment_method = $latest->payment_method;
            }
            $row->save();
        }

        return $order;
    }
}
