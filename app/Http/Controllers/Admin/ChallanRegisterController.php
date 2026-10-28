<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * One register of goods-movement challans: sales delivery challans (from orders)
 * and stock-in challans (from purchases), newest first. Cancelled orders have no
 * challan, so they are left out.
 */
class ChallanRegisterController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct()
    {
        $this->middleware('permission:order-report|purchase-report|report-view', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'from'    => 'nullable|date',
            'to'      => 'nullable|date|after_or_equal:from',
            'type'    => 'nullable|in:all,sales,stock_in',
            'party'   => 'nullable|string|max:100',
            'product' => 'nullable|string|max:100',
        ]);

        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        $type = $data['type'] ?? 'all';
        $party = trim($data['party'] ?? '');
        $product = trim($data['product'] ?? '');

        $rows = collect();

        if ($type !== 'stock_in') {
            $rows = $rows->concat($this->salesRows($from, $to, $party, $product));
        }
        if ($type !== 'sales') {
            $rows = $rows->concat($this->stockInRows($from, $to, $party, $product));
        }

        $sorted = $rows->sortByDesc('date')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();

        $challans = new LengthAwarePaginator(
            $sorted->forPage($page, self::PER_PAGE),
            $sorted->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('backEnd.reports.challans', [
            'challans'     => $challans,
            'salesCount'   => $sorted->where('kind', 'sales')->count(),
            'stockInCount' => $sorted->where('kind', 'stock_in')->count(),
            'filters'      => compact('type', 'party', 'product') + [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
        ]);
    }

    private function salesRows(Carbon $from, Carbon $to, string $party, string $product)
    {
        $orders = Order::whereBetween('created_at', [$from, $to])
            ->where('order_status', '!=', OrderStatus::CANCELLED->value)
            ->with(['orderdetails', 'shipping', 'customer'])
            ->when($party !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('shipping', fn ($s) => $s->where('name', 'like', "%{$party}%"))
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$party}%"))))
            ->when($product !== '', fn ($q) => $q->whereHas('orderdetails',
                fn ($d) => $d->where('product_name', 'like', "%{$product}%")))
            ->get();

        return $orders->map(fn (Order $order) => [
            'kind'  => 'sales',
            'date'  => $order->created_at,
            'ref'   => $order->invoice_display ?? $order->invoice_id,
            'party' => $order->shipping?->name ?? $order->customer?->name ?? '—',
            'items' => $order->orderdetails->pluck('product_name')->filter()->implode(', '),
            'qty'   => (int) $order->orderdetails->sum('qty'),
            'url'   => route('admin.order.challan', ['invoice_id' => $order->invoice_id]),
        ]);
    }

    private function stockInRows(Carbon $from, Carbon $to, string $party, string $product)
    {
        $purchases = Purchase::whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->with(['supplier', 'items.product'])
            ->when($party !== '', fn ($q) => $q->whereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$party}%")))
            ->when($product !== '', fn ($q) => $q->whereHas('items.product',
                fn ($p) => $p->where('name', 'like', "%{$product}%")))
            ->get();

        return $purchases->map(fn (Purchase $purchase) => [
            'kind'  => 'stock_in',
            'date'  => Carbon::parse($purchase->purchase_date),
            'ref'   => $purchase->invoice_no ?? $purchase->id,
            'party' => $purchase->supplier?->name ?? '—',
            'items' => $purchase->items->pluck('product.name')->filter()->implode(', '),
            'qty'   => (int) $purchase->items->sum('qty'),
            'url'   => route('purchases.challan', $purchase->id),
        ]);
    }
}
