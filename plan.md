# Plan: Sync `products.stock` (and variant stock) from `stock_batches`

## ✅ Implementation Status (updated 2026-08-04)

- **Step 1 — done**: `StockManagementService::syncStockFromBatches()` added to
  `app/Services/StockManagementService.php`.
- **Step 2 — done**: Artisan command `stock:sync-from-batches` created in
  `app/Console/Commands/SyncProductStockFromBatches.php` (auto-discovered, no Kernel registration needed).
- **Applied + verified**: `php artisan stock:sync-from-batches --product=22` fixed
  TP Link A54 (`stock` 0 → 10). Full DB dry-run now reports **0 mismatches**.
  Product edit page "Total Stock" badge = batch table "Total" = 10; POS shows
  "Stock: 10" and "Batch (10 avail)".
- **Step 3 — pending**: root-cause drift points (table below) still need to be
  routed through the service. Do this if/when the drift recurs.
- **Step 4 — pending**: prevention accessor / nightly reconcile (optional).

## 1. Problem Statement

- A product shows **batch total = 10** but **stock = 0**.
- The same 0-stock is shown in **POS** (`OrderController`) and in the **frontend/storefront**.
- Root cause: `products.stock` is a **denormalized copy** of available stock. The **source of truth is `stock_batches.remaining_qty`**, but the `products.stock` column has drifted out of sync (stale/0) for some products.

### Where each value comes from today

| Value shown | Source | Current value (example product) |
|---|---|---|
| "Total Stock" badge on product edit | `products.stock` column | 0 ❌ |
| Batch table "Total" on product edit | `SUM(stock_batches.remaining_qty)` | 10 ✅ |
| POS product list stock | `products.stock` column | 0 ❌ |
| Frontend product page stock | `products.stock` column | 0 ❌ |
| Admin stock report / dashboard | `SUM(products.stock)` | wrong ❌ |

> Evidence: `resources/views/backEnd/product/edit.blade.php` line 559 uses `$edit_data->stock`
> and line 612 uses `$edit_data->stockBatches->sum('remaining_qty')`.

## 2. Goal

Bring `products.stock` (and `product_variant_prices.stock`) **back in sync** with
`stock_batches` so POS, frontend, admin reports, and the product edit page all show the
same correct number. Then **prevent the drift from recurring**.

**Decision (policy):** For each product,
`products.stock = SUM(stock_batches.remaining_qty)`.
Products with **no batch rows** are **left untouched** (their current `products.stock`
value is kept) — do not zero out products that legitimately hold stock with no batch ledger.

## 3. Implementation Steps

### Step 1 — Add a reusable sync helper

Add a method to `app/Services/StockManagementService.php` (single source of truth):

```php
/**
 * Recalculate products.stock (and variant stock) from stock_batches.
 * Returns the number of products updated.
 */
public function syncStockFromBatches(?int $productId = null): int
{
    $query = StockBatch::query()
        ->selectRaw('product_id')
        ->selectRaw('SUM(remaining_qty) as total_remaining')
        ->where('remaining_qty', '>', 0)
        ->groupBy('product_id');

    if ($productId) {
        $query->where('product_id', $productId);
    }

    $totals = $query->get()->keyBy('product_id');
    $count  = 0;

    DB::transaction(function () use ($totals, $productId, &$count) {
        $products = Product::query()
            ->when($productId, fn ($q) => $q->where('id', $productId))
            ->get(['id', 'stock']);

        foreach ($products as $product) {
            $computed = (int) ($totals[$product->id]->total_remaining ?? 0);
            // Products with no batches: keep existing stock untouched
            if (!$totals->has($product->id)) {
                continue;
            }
            if ((int) $product->stock !== $computed) {
                $product->update(['stock' => $computed]);
                $count++;
            }
        }
    });

    return $count;
}
```

> **Variant note:** if a product uses variant batches (`variant_price_id` set on
> `stock_batches`), also sync `product_variant_prices.stock`:
> `SUM(remaining_qty) GROUP BY variant_price_id`. Add this in the same transaction.

### Step 2 — Create an Artisan command for the one-time backfill

Create `app/Console/Commands/SyncProductStockFromBatches.php`:

```php
class SyncProductStockFromBatches extends Command
{
    protected $signature = 'stock:sync-from-batches {--product= : Only sync this product id} {--dry-run : Report only, no writes}';

    public function handle(StockManagementService $stockService)
    {
        $this->info('Syncing products.stock from stock_batches ...');

        $productId = $this->option('product') ? (int) $this->option('product') : null;

        // --- dry-run report of mismatches ---
        $rows = DB::table('products as p')
            ->leftJoin('stock_batches as sb', 'sb.product_id', '=', 'p.id')
            ->where('sb.remaining_qty', '>', 0)
            ->selectRaw('p.id, p.name, p.stock as db_stock, SUM(sb.remaining_qty) as batch_stock')
            ->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->groupBy('p.id', 'p.name', 'p.stock')
            ->havingRaw('p.stock <> SUM(sb.remaining_qty)')
            ->orderBy('p.id')
            ->get();

        $this->table(['ID', 'Name', 'products.stock', 'batch sum'], $rows->toArray());
        $this->info('Mismatched products: ' . $rows->count());

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes written.');
            return 0;
        }

        $updated = $stockService->syncStockFromBatches($productId);
        $this->info("Done. Updated {$updated} product(s).");
        return 0;
    }
}
```

Register the command in `app/Console/Kernel.php` (Laravel auto-discovers commands in
`app/Console/Commands/`, so registration is automatic).

Run it:

```bash
# Check what will change first
php artisan stock:sync-from-batches --dry-run

# Apply the fix for all products
php artisan stock:sync-from-batches

# Or for one product (e.g. id 22)
php artisan stock:sync-from-batches --product=22
```

### Step 3 — Fix the root-cause drift points

The following places mutate `products.stock` **without** going through
`StockManagementService` (so batches and the stock column diverge). Audit each and
route through the service (or call `syncStockFromBatches($productId)` after mutating):

| File | Line(s) | What it does |
|---|---|---|
| `app/Http/Controllers/Admin/IncompleteOrderController.php` | ~196 | `$product->stock -= $qty` direct |
| `app/Http/Controllers/Admin/RedXWebhookController.php` | 145, 159 | direct stock += / -= |
| `app/Http/Controllers/Admin/RefundController.php` | 234 | `$product->stock += $qty` direct |
| `app/Http/Controllers/Admin/WarrantyController.php` | 713–716 | `decrement('stock', 1)` direct |
| `app/Http/Controllers/Admin/OrderController.php` | 102 | `decrement('stock', $qty)` fallback |
| `app/Http/Controllers/Admin/PurchaseController.php` | 342–345 | direct `decrement('stock', ...)` on product/variant |
| `app/Http/Controllers/Admin/DemoController.php` | 721, 754 | seeds `products.stock` and `stock_batches` separately — batch quantity must equal stock, or call `syncStockFromBatches` after insert |
| `app/Http/Controllers/Api/Mobile/OrderController.php` | 209 | `$product->stock = max(0, ...)` direct |
| `app/Http/Controllers/Api/Mobile/CartController.php` | 91, 123 | **reads** `products.stock` for availability check — should read batch total |

**Recommended pattern** for each direct mutation: replace with
`$stockService->stockIn(...)` / `$stockService->stockOut(...)`, and where that is not
possible (e.g. webhook/refund), call `app(StockManagementService::class)->syncStockFromBatches($productId)`
immediately after the mutation.

### Step 4 — Prevent recurrence (long-term consistency)

1. **Availability checks** (`CartController`, POS `cart_add`, frontend "add to cart")
   should validate against `SUM(stock_batches.remaining_qty)` — or `products.stock`
   **after** it is guaranteed synced. Add a Product accessor:
   ```php
   public function getBatchStockAttribute(): int
   {
       return (int) $this->stockBatches()->sum('remaining_qty');
   }
   ```
2. Make `stockIn()` / `stockOut()` / `adjustStock()` the **only** entry points that
   change stock. Keep the `products.stock` increment/decrement inside the service.
3. Optional: schedule a nightly reconcile command in `app/Console/Kernel.php`:
   ```php
   $schedule->command('stock:sync-from-batches')->dailyAt('02:00');
   ```
   (Only if drift is still happening after Step 3; otherwise skip.)

### Step 5 — Verification checklist

1. Reopen the product edit page: **"Total Stock" badge == batch table "Total"** (e.g. 10 == 10).
2. POS order page: product stock shows the correct value (10), and it appears in the
   "in stock" list if a stock filter is used.
3. Frontend product page: stock = 10; "add to cart" works and cart allows qty up to 10.
4. `php artisan stock:sync-from-batches --dry-run` reports **0 mismatches**.
5. Spot check admin reports (`StockController::index`, `DashboardController`) totals match
   `SUM(stock_batches.remaining_qty)`.

## 4. Rollback / Safety

- The command only **updates** `products.stock` / variant stock; it never deletes batches
  or creates new ones, so it is safe to re-run.
- Snapshot before running:
  ```sql
  CREATE TABLE products_stock_backup_20260804 AS
  SELECT id, name, stock FROM products;
  ```
- Run the command inside a transaction (already wrapped in `DB::transaction`).

## 5. Acceptance Criteria

- [ ] `products.stock` equals `SUM(stock_batches.remaining_qty)` for every product that has batches.
- [ ] POS, frontend, and admin reports all show the same stock value as the product edit batch table.
- [ ] Direct stock mutations are removed or wrapped so the two sources can no longer diverge.
- [ ] `stock:sync-from-batches --dry-run` reports zero mismatches after a week of normal usage.
