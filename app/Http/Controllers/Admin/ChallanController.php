<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\Purchase;

/**
 * Goods movement challans: the sales delivery challan (goods leaving with a customer)
 * and the stock-in challan (goods arriving from a supplier). Both are printed pages
 * without prices, so they can travel with the goods.
 */
class ChallanController extends Controller
{
    /** Sales delivery challan for one order, looked up by its invoice id. */
    public function delivery($invoice_id)
    {
        $order = Order::where('invoice_id', $invoice_id)
            ->with(['orderdetails.color', 'orderdetails.size', 'shipping', 'customer'])
            ->firstOrFail();

        return view('backEnd.challan.delivery', $this->letterhead() + compact('order'));
    }

    /** Stock-in challan for one purchase (goods received from the supplier). */
    public function stockIn($id)
    {
        $purchase = Purchase::with(['supplier', 'items.product'])->findOrFail($id);

        return view('backEnd.challan.stock_in', $this->letterhead() + compact('purchase'));
    }

    /** Company name, address and contact, shared by both challans. */
    private function letterhead(): array
    {
        return [
            'generalsetting' => GeneralSetting::first(),
            'contact' => Contact::first(),
        ];
    }
}
