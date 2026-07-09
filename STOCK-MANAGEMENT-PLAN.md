# Stock Management System - Implementation Plan

## Overview
A complete stock management system with barcode support, supplier management, selectable LIFO/FIFO costing methods, and POS enhancements including barcode scanning and customer hold functionality.

---

## 1. Database Schema Changes

### 1.1 New Tables

#### `stock_batches`
Tracks each purchase batch for LIFO/FIFO costing.

```sql
CREATE TABLE stock_batches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    variant_price_id BIGINT UNSIGNED NULL,
    purchase_id     BIGINT UNSIGNED NULL,
    supplier_id     BIGINT UNSIGNED NULL,
    batch_no        VARCHAR(50) NULL,
    quantity        INT NOT NULL DEFAULT 0,          -- positive = in, negative = out
    remaining_qty   INT NOT NULL DEFAULT 0,          -- current available from this batch
    unit_cost       DECIMAL(14,2) NOT NULL DEFAULT 0,
    selling_price   DECIMAL(14,2) NULL,
    mfg_date        DATE NULL,
    exp_date        DATE NULL,
    type            ENUM('in','out') NOT NULL DEFAULT 'in',
    reference_type  VARCHAR(50) NULL,                -- 'purchase', 'sale_return', 'purchase_return', 'adjustment'
    reference_id    BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX stock_batches_product_idx (product_id),
    INDEX stock_batches_purchase_idx (purchase_id),
    INDEX stock_batches_supplier_idx (supplier_id)
);
```

#### `stock_adjustments`
Logs all manual stock adjustments.

```sql
CREATE TABLE stock_adjustments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    variant_price_id BIGINT UNSIGNED NULL,
    type            ENUM('addition','reduction','correction') NOT NULL,
    quantity        INT NOT NULL,
    current_stock   INT NOT NULL,
    new_stock       INT NOT NULL,
    reason          TEXT NULL,
    reference_type  VARCHAR(50) NULL,
    reference_id    BIGINT UNSIGNED NULL,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,

    INDEX stock_adjustments_product_idx (product_id)
);
```

#### `supplier_returns`
Tracks product returns to suppliers.

```sql
CREATE TABLE supplier_returns (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id     BIGINT UNSIGNED NOT NULL,
    purchase_id     BIGINT UNSIGNED NULL,
    return_no       VARCHAR(50) NOT NULL,
    return_date     DATE NOT NULL,
    total_qty       INT NOT NULL DEFAULT 0,
    total_amount    DECIMAL(15,2) NOT NULL DEFAULT 0,
    reason          TEXT NULL,
    status          ENUM('pending','completed','cancelled') DEFAULT 'pending',
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX supplier_returns_supplier_idx (supplier_id)
);
```

#### `supplier_return_items`
Line items for supplier returns.

```sql
CREATE TABLE supplier_return_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_return_id BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    variant_price_id BIGINT UNSIGNED NULL,
    batch_id        BIGINT UNSIGNED NULL,
    qty             INT NOT NULL,
    unit_cost       DECIMAL(14,2) NOT NULL,
    line_total      DECIMAL(14,2) NOT NULL,
    reason          TEXT NULL,
    created_at      TIMESTAMP NULL,

    INDEX supplier_return_items_return_idx (supplier_return_id)
);
```

#### `pos_hold_carts`
Stores held POS carts for later retrieval.

```sql
CREATE TABLE pos_hold_carts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NULL,
    customer_name   VARCHAR(255) NULL,
    customer_phone  VARCHAR(50) NULL,
    cart_data       JSON NOT NULL,                   -- serialized cart contents
    subtotal        DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(15,2) NOT NULL DEFAULT 0,
    shipping_charge DECIMAL(15,2) NOT NULL DEFAULT 0,
    grand_total     DECIMAL(15,2) NOT NULL DEFAULT 0,
    note            TEXT NULL,
    held_by         BIGINT UNSIGNED NULL,            -- user who held it
    held_at         TIMESTAMP NULL,
    restored_at     TIMESTAMP NULL,
    status          ENUM('held','restored','converted','cancelled') DEFAULT 'held',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX pos_hold_carts_customer_idx (customer_id),
    INDEX pos_hold_carts_held_by_idx (held_by),
    INDEX pos_hold_carts_status_idx (status)
);
```

### 1.2 Modifications to Existing Tables

#### `products` — Add barcode & costing columns
```sql
ALTER TABLE products ADD COLUMN barcode VARCHAR(255) NULL AFTER product_code;
ALTER TABLE products ADD COLUMN barcode_type ENUM('code128','code39','ean13','ean8','upca','upce','qr') DEFAULT 'code128' AFTER barcode;
ALTER TABLE products ADD COLUMN costing_method ENUM('lifo','fifo','average') DEFAULT 'average' AFTER purchase_price;
ALTER TABLE products ADD COLUMN low_stock_threshold INT DEFAULT 10 AFTER stock;
ALTER TABLE products ADD COLUMN allow_negative_stock TINYINT(1) DEFAULT 0 AFTER low_stock_threshold;
ALTER TABLE products ADD COLUMN weight DECIMAL(10,2) NULL AFTER pro_unit;
```

#### `product_variant_prices` — Add barcode
```sql
ALTER TABLE product_variant_prices ADD COLUMN barcode VARCHAR(255) NULL AFTER sku;
```

#### `purchases` — Add costing method override
```sql
ALTER TABLE purchases ADD COLUMN costing_method ENUM('lifo','fifo','average') NULL AFTER invoice_no;
```

#### `purchase_items` — Add batch tracking fields
```sql
ALTER TABLE purchase_items ADD COLUMN batch_no VARCHAR(50) NULL AFTER return_qty;
ALTER TABLE purchase_items ADD COLUMN mfg_date DATE NULL AFTER batch_no;
ALTER TABLE purchase_items ADD COLUMN exp_date DATE NULL AFTER mfg_date;
```

#### `order_details` — Add batch reference for COGS calculation
```sql
ALTER TABLE order_details ADD COLUMN batch_ids JSON NULL AFTER qty;  -- [{batch_id: 1, qty: 2}, ...]
ALTER TABLE order_details ADD COLUMN cogs DECIMAL(15,2) NULL AFTER batch_ids;
```

---

## 2. Costing Methods Logic

### 2.1 Configuration
- A global default costing method in `GeneralSetting` (default: `average`)
- Per-product override in `products.costing_method`
- Per-purchase override in `purchases.costing_method`
- Selection precedence: purchase > product > global

### 2.2 Algorithm for Each Method

#### **FIFO (First In, First Out)**
```
On Purchase (stock in):
  - Create stock_batches record with remaining_qty = qty_purchased
  - Add to product.stock

On Sale (stock out):
  - Fetch all stock_batches for product with remaining_qty > 0, ordered by created_at ASC
  - Deduct from oldest batches first
  - Calculate COGS = SUM(batch.unit_cost * qty_deducted_from_batch)
  - Update stock_batches.remaining_qty
  - Decrease product.stock

On Purchase Return:
  - Remove from the most recent batch (FIFO inverse)
  - Or add a negative batch entry
```

#### **LIFO (Last In, First Out)**
```
On Purchase (stock in):
  - Same as FIFO: create stock_batches record

On Sale (stock out):
  - Fetch all stock_batches for product with remaining_qty > 0, ordered by created_at DESC
  - Deduct from newest batches first
  - Calculate COGS = SUM(batch.unit_cost * qty_deducted_from_batch)
  - Update stock_batches.remaining_qty
  - Decrease product.stock

On Purchase Return:
  - Remove from the oldest batch (LIFO inverse)
  - Or add a negative batch entry
```

#### **Average Cost (Weighted Average)**
```
On Purchase (stock in):
  - Update product.purchase_price = ((product.purchase_price * product.stock) + (unit_cost * qty)) / (product.stock + qty)
  - Still create stock_batches for traceability
  - Add to product.stock

On Sale (stock out):
  - COGS = product.purchase_price * qty_sold
  - Decrease product.stock
  - (Optional) deduct proportionally from batches

On Purchase Return:
  - Recalculate average (reverse the weighted average formula)
  - Decrease stock
```

### 2.3 Service Class

Create `app/Services/StockManagementService.php`:

```php
class StockManagementService
{
    public function stockIn(Product $product, array $data): void;
    public function stockOut(Product $product, int $qty, array $reference = []): array; // returns [cogs, batch_details]
    public function calculateCogs(Product $product, int $qty): float;
    public function getAvailableBatches(Product $product, string $method = null): Collection;
    public function getCurrentValuation(Product $product): float;
    public function adjustStock(Product $product, int $qty, string $type, string $reason): void;
}
```

---

## 3. Barcode Management

### 3.1 Barcode Generation
- Auto-generate barcode from `product_code` if none provided
- Support multiple formats: Code128, Code39, EAN-13, EAN-8, UPC-A, UPC-E
- Display barcode on:
  - Product detail page
  - Purchase receipts
  - Sale invoices
  - Barcode labels (printable)

### 3.2 Barcode Scanning on POS
- Add barcode input field at top of POS page
- On Enter / scanner trigger:
  1. Look up product by barcode in `products.barcode`
  2. If not found, look up in `product_variant_prices.barcode`
  3. If found, auto-add to cart
  4. If not found, flash error "Product not found"
- Support multiple barcode scans at once (continuous scanning)

### 3.3 Barcode Printing
- Print barcode labels for products
- Configurable label size (2x1", 3x2", A4 sheet)
- Bulk barcode printing for multiple products

---

## 4. Supplier Management Enhancements

### 4.1 Current State (already exists)
- `suppliers` table with basic info
- `supplier_payments` table
- Basic supplier-purchase linkage

### 4.2 Improvements
Add to `suppliers` table:
```sql
ALTER TABLE suppliers ADD COLUMN contact_person VARCHAR(255) NULL;
ALTER TABLE suppliers ADD COLUMN tax_id VARCHAR(100) NULL;
ALTER TABLE suppliers ADD COLUMN payment_terms VARCHAR(100) NULL;  -- 'cod', '15days', '30days', '60days'
ALTER TABLE suppliers ADD COLUMN lead_time INT NULL;  -- in days
ALTER TABLE suppliers ADD COLUMN notes TEXT NULL;
ALTER TABLE suppliers ADD COLUMN is_active TINYINT(1) DEFAULT 1;
```

### 4.3 Supplier Dashboard
- Supplier-wise purchase history
- Supplier-wise due/payment tracking
- Supplier product catalog
- Supplier performance metrics (delivery time, return rate)

---

## 5. POS Enhancements

### 5.1 Barcode Scanning Integration
- Add to `resources/views/backEnd/order/create.blade.php`:
  - Barcode search input field at top of POS
  - JavaScript keypress handler that listens for Enter key
  - AJAX call to find product by barcode
  - Auto-add to cart

- Add route: `GET /admin/order/scan-barcode/{barcode}` → `OrderController@scanBarcode`
- Controller method:
```php
public function scanBarcode($barcode)
{
    // Find product by barcode
    $product = Product::where('barcode', $barcode)->first();
    
    // If not found, try variant barcode
    if (!$product) {
        $variant = ProductVariantPrice::where('barcode', $barcode)->with('product')->first();
        if ($variant) {
            // Add variant to cart with size/color
            return $this->addVariantToCart($variant);
        }
    }
    
    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }
    
    // Add to cart (same as clicking product)
    return $this->cart_add(new Request(['id' => $product->id]));
}
```

### 5.2 Customer Hold Option

#### Frontend (POS Page)
- Add "Hold Cart" button next to "Complete Sale"
- Add "Held Orders" panel/button to show held carts

#### Hold Cart Flow
1. User clicks "Hold Cart" button
2. Modal opens asking for:
   - Customer name/phone (optional, can fill later)
   - Note (optional)
3. Current cart data is serialized and saved to `pos_hold_carts`
4. Cart is cleared from session
5. A notification shows the hold reference number

#### Restore Hold Flow
1. User clicks "Held Orders" → shows list of held carts
2. Each held cart shows:
   - Hold reference #
   - Customer name/phone
   - Date/time held
   - Item count
   - Grand total
   - "Restore" and "Delete" buttons
3. Clicking "Restore" loads the cart back into the POS session
4. Customer info fields pre-filled if available

#### Controller Methods
```php
// Hold cart
public function holdCart(Request $request)
{
    $cartData = Cart::instance('pos_shopping')->content();
    // Calculate totals, save to pos_hold_carts
    // Clear cart
    // Return success
}

// List held carts
public function heldCarts()
{
    $heldCarts = PosHoldCart::where('status', 'held')
        ->where('held_by', auth()->id())
        ->orderBy('held_at', 'desc')
        ->get();
    return view('backEnd.order.held_carts', compact('heldCarts'));
}

// Restore held cart
public function restoreHold($id)
{
    $heldCart = PosHoldCart::findOrFail($id);
    // Clear current cart
    // Restore cart data from JSON
    // Mark as restored
    // Return to POS page with loaded cart
}

// Delete held cart
public function deleteHold($id)
{
    PosHoldCart::findOrFail($id)->update(['status' => 'cancelled']);
    return back()->with('success', 'Held cart removed');
}
```

### 5.3 POS Additional Improvements
- Quick customer search/select dropdown
- Order history for current customer
- Payment split (multiple payment methods)
- Quick print receipt after sale

---

## 6. Routes

```php
// Barcode
Route::get('admin/order/scan-barcode/{barcode}', [OrderController::class, 'scanBarcode'])->name('admin.order.scan_barcode');

// Hold Cart
Route::post('admin/order/hold-cart', [OrderController::class, 'holdCart'])->name('admin.order.hold_cart');
Route::get('admin/order/held-carts', [OrderController::class, 'heldCarts'])->name('admin.order.held_carts');
Route::get('admin/order/restore-hold/{id}', [OrderController::class, 'restoreHold'])->name('admin.order.restore_hold');
Route::delete('admin/order/delete-hold/{id}', [OrderController::class, 'deleteHold'])->name('admin.order.delete_hold');

// Stock adjustments
Route::get('admin/stock/adjustments', [StockController::class, 'adjustments'])->name('admin.stock.adjustments');
Route::post('admin/stock/adjust', [StockController::class, 'adjust'])->name('admin.stock.adjust');
Route::get('admin/stock/batches/{product}', [StockController::class, 'batches'])->name('admin.stock.batches');

// Supplier returns
Route::get('admin/supplier-returns', [SupplierReturnController::class, 'index'])->name('admin.supplier_returns.index');
Route::post('admin/supplier-returns/store', [SupplierReturnController::class, 'store'])->name('admin.supplier_returns.store');

// Barcode labels
Route::get('admin/products/barcode-labels', [ProductController::class, 'barcodeLabels'])->name('admin.products.barcode_labels');
Route::post('admin/products/print-barcodes', [ProductController::class, 'printBarcodes'])->name('admin.products.print_barcodes');

// Stock valuation report
Route::get('admin/reports/stock-valuation', [ReportController::class, 'stockValuation'])->name('admin.reports.stock_valuation');
Route::get('admin/reports/cogs', [ReportController::class, 'cogsReport'])->name('admin.reports.cogs');
```

---

## 7. New Controllers

### 7.1 `StockController`
- `index()` — Stock overview dashboard
- `adjustments()` — List stock adjustments
- `adjust(Request)` — Create stock adjustment
- `batches(Product)` — View batch history for a product
- `valuation()` — Stock valuation report

### 7.2 `SupplierReturnController`
- `index()` — List supplier returns
- `store(Request)` — Create supplier return
- `show($id)` — View return details
- `destroy($id)` — Delete/cancel return

---

## 8. Models to Create

### `StockBatch`
```php
class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'variant_price_id', 'purchase_id', 'supplier_id',
        'batch_no', 'quantity', 'remaining_qty', 'unit_cost', 'selling_price',
        'mfg_date', 'exp_date', 'type', 'reference_type', 'reference_id'
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function variant() { return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id'); }
}
```

### `StockAdjustment`
```php
class StockAdjustment extends Model { ... }
```

### `SupplierReturn`
```php
class SupplierReturn extends Model { ... }
```

### `SupplierReturnItem`
```php
class SupplierReturnItem extends Model { ... }
```

### `PosHoldCart`
```php
class PosHoldCart extends Model
{
    protected $fillable = [
        'customer_id', 'customer_name', 'customer_phone',
        'cart_data', 'subtotal', 'discount', 'shipping_charge',
        'grand_total', 'note', 'held_by', 'held_at', 'restored_at', 'status'
    ];

    protected $casts = [
        'cart_data' => 'json',
        'held_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function heldBy() { return $this->belongsTo(User::class, 'held_by'); }
}
```

---

## 9. Views to Create/Modify

### 9.1 New Views
- `backEnd/stock/index.blade.php` — Stock management dashboard
- `backEnd/stock/adjustments.blade.php` — Stock adjustments list
- `backEnd/stock/batches.blade.php` — Batch history for a product
- `backEnd/stock/valuation.blade.php` — Stock valuation report
- `backEnd/supplier_returns/index.blade.php` — Supplier returns list
- `backEnd/supplier_returns/create.blade.php` — Create supplier return
- `backEnd/supplier_returns/show.blade.php` — View return details
- `backEnd/order/held_carts.blade.php` — Held carts list
- `backEnd/order/hold_cart_modal.blade.php` — Hold cart modal
- `backEnd/products/barcode_labels.blade.php` — Barcode label printing
- `backEnd/reports/cogs.blade.php` — COGS report

### 9.2 Modified Views
- `backEnd/order/create.blade.php` — Add barcode input, hold button, held carts panel
- `backEnd/product/create.blade.php` — Add barcode field, costing method selector
- `backEnd/product/edit.blade.php` — Add barcode field, costing method selector
- `backEnd/product/show.blade.php` — Display barcode, show batch info
- `backEnd/purchases/index.blade.php` — Add batch fields, costing method
- `backEnd/purchases/create.blade.php` — Add barcode scan on purchase receive
- `backEnd/reports/stock.blade.php` — Improve with batch-level detail

---

## 10. Implementation Phases

### Phase 1: Database & Models (Days 1-2)
1. Create migrations for all new tables
2. Modify existing tables with new columns
3. Create Eloquent models
4. Add relationships

### Phase 2: Core Stock Engine (Days 3-5)
1. Create `StockManagementService` with LIFO/FIFO/Average algorithms
2. Integrate with purchase flow (auto batch creation)
3. Integrate with order flow (auto batch deduction + COGS calculation)
4. Stock adjustment functionality

### Phase 3: Barcode System (Days 6-7)
1. Add barcode fields to product create/edit
2. Implement barcode generation
3. Barcode scanning on POS
4. Barcode label printing

### Phase 4: POS Hold Cart (Days 8-9)
1. Create `pos_hold_carts` table and model
2. Hold cart modal UI
3. Hold/Restore/Delete functionality
4. Held carts list panel

### Phase 5: Supplier Returns & Reports (Days 10-11)
1. Supplier return CRUD
2. Stock valuation report
3. COGS report
4. Batch traceability views

### Phase 6: UI Polish & Testing (Days 12-14)
1. Responsive design improvements
2. Error handling
3. Testing all flows
4. Documentation

---

## 11. Key Service Methods — Pseudocode

### `StockManagementService::stockOut()`

```php
public function stockOut(Product $product, int $qty, array $reference = []): array
{
    $method = $this->resolveMethod($product);
    $batches = StockBatch::where('product_id', $product->id)
        ->where('remaining_qty', '>', 0)
        ->where('type', 'in')
        ->orderBy('created_at', $method === 'fifo' ? 'asc' : 'desc')
        ->get();

    $remaining = $qty;
    $totalCogs = 0;
    $batchDetails = [];

    foreach ($batches as $batch) {
        if ($remaining <= 0) break;
        
        $deduct = min($remaining, $batch->remaining_qty);
        $cogs = $deduct * $batch->unit_cost;
        
        $batch->decrement('remaining_qty', $deduct);
        
        $batchDetails[] = [
            'batch_id' => $batch->id,
            'qty' => $deduct,
            'unit_cost' => $batch->unit_cost,
            'cogs' => $cogs,
        ];
        
        $totalCogs += $cogs;
        $remaining -= $deduct;
    }

    if ($remaining > 0) {
        throw new \Exception("Insufficient stock for product: {$product->name}");
    }

    // Create outflow batch record
    StockBatch::create([
        'product_id' => $product->id,
        'quantity' => -$qty,
        'remaining_qty' => 0,
        'unit_cost' => 0,
        'type' => 'out',
        'reference_type' => $reference['type'] ?? 'sale',
        'reference_id' => $reference['id'] ?? null,
    ]);

    // Update product stock
    $product->decrement('stock', $qty);

    return [
        'cogs' => $totalCogs,
        'batch_details' => $batchDetails,
    ];
}
```

---

## 12. Admin Menu Integration

Add to admin sidebar:
```
Stock Management
├── Stock Dashboard
├── Stock Adjustments
├── Stock Batches
├── Stock Valuation Report
├── COGS Report
├── Supplier Returns
├── Barcode Labels
```

POS Enhancements:
```
POS Page
├── Barcode Scanner Input
├── [Hold Cart] Button
├── [Held Orders] Button → Modal/Page
```

---

## 13. Dependencies

### Composer Packages
- `milon/barcode` — For barcode generation (or `barryvdh/laravel-dompdf` + barcode fonts)
- Alternative: `picqer/php-barcode-generator` — Lightweight barcode generation

### NPM Packages
- `quagga` or `zxing/browser` — For camera barcode scanning (optional enhancement)

---

## 14. Quick Implementation Checklist

- [x] Create migrations for: `stock_batches`, `stock_adjustments`, `supplier_returns`, `supplier_return_items`, `pos_hold_carts`
- [x] Add columns: `barcode` to products, `costing_method` to products, `barcode` to product_variant_prices
- [x] Create models: `StockBatch`, `StockAdjustment`, `SupplierReturn`, `SupplierReturnItem`, `PosHoldCart`
- [x] Create `StockManagementService` with LIFO/FIFO/Average
- [x] Modify `PurchaseController@store` to create stock batches
- [x] Modify `OrderController@order_store` and `handleStockChange` to use batch deduction + COGS
- [x] Add barcode scanning route + controller method
- [x] Add barcode input to POS view + JavaScript handler
- [x] Add "Hold Cart" + "Held Orders" to POS
- [x] Create stock adjustment CRUD (StockController + views)
- [x] Create supplier return CRUD (StockController + views)
- [x] Create stock valuation & COGS reports
- [x] Add barcode label printing functionality
- [ ] Test all flows end-to-end
- [ ] Fix: `hold_cart` route method – ensure POST works with frontend
- [ ] Fix: `restore_hold` – verify cart restoration works with session
- [ ] Add supplier fields (contact_person, tax_id, payment_terms) to supplier create/edit views
