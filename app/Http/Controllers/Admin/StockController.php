<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockAdjustment;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\StockManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockManagementService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Stock Dashboard – overview of stock health
     */
    public function index()
    {
        $totalProducts   = Product::count();
        $totalStockQty   = Product::sum('stock');
        $totalStockValue = Product::sum(DB::raw('stock * purchase_price'));

        $lowStockProducts = Product::where('low_stock_threshold', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();

        $recentBatches    = StockBatch::with('product')
            ->latest()
            ->take(10)
            ->get();

        $recentAdjustments = StockAdjustment::with('product', 'creator')
            ->latest()
            ->take(10)
            ->get();

        // Costing method distribution
        $costingMethods = Product::select('costing_method', DB::raw('count(*) as total'))
            ->groupBy('costing_method')
            ->pluck('total', 'costing_method');

        return view('backEnd.stock.dashboard', compact(
            'totalProducts',
            'totalStockQty',
            'totalStockValue',
            'lowStockProducts',
            'recentBatches',
            'recentAdjustments',
            'costingMethods'
        ));
    }

    /**
     * List all stock batches
     */
    public function batches(Request $request)
    {
        $query = StockBatch::with('product', 'supplier', 'purchase');

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by type (in/out)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Search by batch no
        if ($request->filled('batch_no')) {
            $query->where('batch_no', 'like', '%' . $request->batch_no . '%');
        }

        $batches  = $query->latest()->paginate(25);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'company']);

        return view('backEnd.stock.batches', compact('batches', 'products', 'suppliers'));
    }

    /**
     * List stock adjustments
     */
    public function adjustments(Request $request)
    {
        $query = StockAdjustment::with('product', 'creator');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $adjustments = $query->latest()->paginate(25);
        $products    = Product::orderBy('name')->get(['id', 'name']);

        return view('backEnd.stock.adjustments', compact('adjustments', 'products'));
    }

    /**
     * Show form to create a manual stock adjustment
     */
    public function createAdjustment()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'stock', 'barcode']);
        $productsJson = json_encode($products->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'stock' => $p->stock, 'barcode' => $p->barcode ?? ''];
        }));
        return view('backEnd.stock.adjustment_create', compact('products', 'productsJson'));
    }

    /**
     * Store a manual stock adjustment
     */
    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.type'         => 'required|in:addition,reduction,correction',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.reason'       => 'nullable|string|max:500',
        ]);

        $count = 0;
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $currentStock = $product->stock;
            $qty = (float) $item['quantity'];
            $reason = $item['reason'] ?? 'Manual adjustment';

            $newStock = match ($item['type']) {
                'addition'   => $currentStock + $qty,
                'reduction'  => max(0, $currentStock - $qty),
                'correction' => $qty,
            };

            if ($item['variant_id'] ?? null) {
                $variant = \App\Models\ProductVariantPrice::find($item['variant_id']);
                if ($variant) {
                    $variant->stock = $newStock;
                    $variant->save();
                }
            } else {
                $this->stockService->adjustStock($product, $newStock, $item['type'], $reason, auth('admin')->id());
            }

            // Batch-specific adjustment
            if ($item['batch_id'] ?? null) {
                $batch = \App\Models\StockBatch::find($item['batch_id']);
                if ($batch) {
                    $oldQty = $batch->remaining_qty;
                    $batch->remaining_qty = match ($item['type']) {
                        'addition'  => $oldQty + $qty,
                        'reduction' => max(0, $oldQty - $qty),
                        'correction'=> $qty,
                    };
                    $batch->save();
                }
            }

            $count++;
        }

        return redirect()->route('admin.stock.adjustments')
            ->with('success', $count . ' stock adjustment(s) saved.');
    }

    /**
     * Stock Valuation Report
     */
    public function valuation(Request $request)
    {
        $products = Product::with(['stockBatches' => function ($q) {
            $q->where('type', 'in')->where('remaining_qty', '>', 0);
        }])->orderBy('name');

        // Search
        if ($request->filled('search')) {
            $products->where('name', 'like', '%' . $request->search . '%');
        }

        // Category filter
        if ($request->filled('category_id')) {
            $products->where('category_id', $request->category_id);
        }

        $products = $products->paginate(25)->withQueryString();

        $totalValue = 0;
        foreach ($products as $p) {
            $p->valuation = $this->stockService->getCurrentValuation($p);
            $totalValue += $p->valuation;
        }

        return view('backEnd.stock.valuation', compact('products', 'totalValue'));
    }

    /**
     * COGS (Cost of Goods Sold) Report
     */
    public function cogs(Request $request)
    {
        $query = OrderDetails::with('order', 'product')
            ->whereNotNull('cogs')
            ->where('cogs', '>', 0);

        // Date filter
        if ($request->filled('from')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from);
            });
        }
        if ($request->filled('to')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to);
            });
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $details = $query->latest()->paginate(25)->withQueryString();

        // Summary
        $totalCogs    = $details->sum('cogs');
        $totalRevenue = $details->sum('price');
        $totalProfit  = $totalRevenue - $totalCogs;

        return view('backEnd.stock.cogs', compact('details', 'totalCogs', 'totalRevenue', 'totalProfit'));
    }

    /**
     * Print barcode labels for a product/batch
     */
    public function printBarcode(Request $request)
    {
        $product  = null;
        $quantity = $request->quantity ?? 10;
        $batches  = collect();

        if ($request->has('product_id')) {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'integer|min:1|max:100',
            ]);
            $product = Product::findOrFail($request->product_id);
            $quantity = min($request->quantity ?? 10, 100);

            // Fetch batches with remaining stock for this product
            $batches = \App\Models\StockBatch::where('product_id', $product->id)
                ->where('remaining_qty', '>', 0)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'batch_no', 'custom_field', 'remaining_qty']);
        }

        return view('backEnd.stock.barcode_print', compact('product', 'quantity', 'batches'));
    }

    // ============================================================
    //  SUPPLIER RETURNS
    // ============================================================

    public function supplierReturns(Request $request)
    {
        $query = SupplierReturn::with('supplier', 'creator');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $returns  = $query->latest()->paginate(25);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'company']);

        return view('backEnd.stock.supplier_returns', compact('returns', 'suppliers'));
    }

    public function createSupplierReturn()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'company']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'stock']);
        $purchases = Purchase::where('status', 'received')->latest()->get(['id', 'invoice_no']);

        return view('backEnd.stock.supplier_return_create', compact('suppliers', 'products', 'purchases'));
    }

    public function storeSupplierReturn(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'return_date' => 'required|date',
            'reason'      => 'required|string|max:500',
            'items'       => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.batch_id'         => 'nullable|exists:stock_batches,id',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.unit_cost'        => 'required|numeric|min:0',
            'items.*.reason'           => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $totalQty    = 0;
            $totalAmount = 0;
            $items       = [];

            foreach ($request->items as $idx => $item) {
                $lineTotal = $item['qty'] * $item['unit_cost'];
                $totalQty  += $item['qty'];
                $totalAmount += $lineTotal;

                $items[] = new SupplierReturnItem([
                    'product_id'  => $item['product_id'],
                    'batch_id'    => $item['batch_id'] ?? null,
                    'qty'         => $item['qty'],
                    'unit_cost'   => $item['unit_cost'],
                    'line_total'  => $lineTotal,
                    'reason'      => $item['reason'] ?? null,
                ]);

                // Restore stock: increase product stock, decrease batch remaining_qty
                $product = Product::findOrFail($item['product_id']);
                $product->increment('stock', $item['qty']);

                if (!empty($item['batch_id'])) {
                    $batch = StockBatch::find($item['batch_id']);
                    if ($batch) {
                        $batch->increment('remaining_qty', $item['qty']);
                    }
                }
            }

            $return = SupplierReturn::create([
                'supplier_id'  => $request->supplier_id,
                'purchase_id'  => $request->purchase_id,
                'return_no'    => 'SR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'return_date'  => $request->return_date,
                'total_qty'    => $totalQty,
                'total_amount' => $totalAmount,
                'reason'       => $request->reason,
                'status'       => 'completed',
                'created_by'   => auth('admin')->id(),
            ]);

            $return->items()->saveMany($items);

            DB::commit();
            return redirect()->route('admin.stock.supplier-returns')
                ->with('success', 'Supplier return created successfully. Stock has been adjusted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating return: ' . $e->getMessage());
        }
    }

    /**
     * Get batches for a product (AJAX for return form)
     */
    public function getProductBatches($productId)
    {
        $batches = StockBatch::where('product_id', $productId)
            ->where('type', 'in')
            ->where('remaining_qty', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'batch_no', 'unit_cost', 'remaining_qty']);

        return response()->json($batches);
    }
}
