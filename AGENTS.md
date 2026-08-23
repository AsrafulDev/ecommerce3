# AGENTS.md — lara (Ecommerce Pro)

Laravel 12 e-commerce platform ("Ecommerce Pro") with a full admin panel, storefront, POS, stock/batch management, warranty pipeline, and a mobile (Flutter) API. The paired WordPress plugin `softmit-license-manager` acts as its license/update server.

## Commands

- **Tests:** `php artisan test` (Unit + Feature suites; very few tests exist)
- **Frontend build:** `npm run dev` / `npm run build` (Vite — only for `resources/sass` + `resources/js`; the admin/storefront UI uses static assets in `public/backEnd`, `public/frontEnd`)
- **Artisan commands (auto-discovered, no Kernel registration):**
  - `stock:sync-from-batches {--product=} {--dry-run}` — reconcile `products.stock` from `stock_batches`
  - `warranty:expire`, `warranty:update-tiers`
  - `courier:check-status {--limit=50} {--force}` — poll Pathao/Steadfast/RedX
  - `update:release {version} {--upload} {--secret=}` — build/upload update zip to the license server
  - `migrate:fresh:default` — fresh migrate + default seeder only
- **PHP:** `^8.2` (composer platform pinned `8.2.12`). Sail is **not** usable — there is no `docker-compose.yml`.

## Architecture

- **Models** in `app/Models/` (~100): `Product`, `ProductVariantPrice`, `StockBatch`, `StockAdjustment`, `Order`, `Customer`, `Warranty*`, `Version`, `Courierapi`, `Employee*`, etc.
- **Services** in `app/Services/`: `StockManagementService` (stockIn/stockOut/adjustStock/syncStockFromBatches, FIFO/LIFO/average costing), `WarrantyService`, `LicenseService`, `RedXService`, `FacebookCapiService`, `Ads/`.
- **Controllers**: fat controllers; services injected via constructor or resolved ad hoc (`app(StockManagementService::class)`).
- **Enums** in `app/Enums/`: `OrderStatus`, `PaymentStatus`, `Warranty*`, `Damage*`. Order status is enum-driven (`OrderStatus::fromLegacyId()` bridges old ints).
- **Helpers**: `app/helpers.php` (autoloaded via composer `files`) — `log_activity()`, color helpers. `app/Helpers/`: `FundHelper`, `OrderHelper`, `PresetData`.
- **Routes**: `routes/web.php` (admin under `prefix('admin')`, middleware `['auth:admin','admin','lock','check_refer','demo_mode']`); `routes/api.php` (`v1` public, `v1/mobile` Sanctum, `updates` license-protected). Middleware aliases in `bootstrap/app.php`.
- **Views**: admin `resources/views/backEnd/**` (layout `backEnd/layouts/master.blade.php`); storefront `resources/views/frontEnd/**`; the customer panel is a separate Tailwind layout that does **not** load product-card CSS.

## Conventions

- **Permissions**: Spatie `laravel-permission`; gate in controller constructors: `$this->middleware('permission:product-list|product-create', ['only' => [...]]);`
- **Models**: many use `$guarded = []`; Laravel 12 `casts()` method (not `$casts`); `Product` uses slug route-key binding.
- **Flash messages**: `Toastr` facade. **Audit logging**: `log_activity($module, $action, $description, $model, $data)`.
- **Defensive DB checks**: `Schema::hasColumn()` guards against columns missing on live DBs.
- **Comments are a mix of Bengali and English** — preserve them.

## Critical pitfalls

1. **STOCK DRIFT (see `plan.md`):** `products.stock` is a denormalized copy; **source of truth is `stock_batches.remaining_qty`**. Always mutate stock through `StockManagementService::stockIn/stockOut/adjustStock`, or call `syncStockFromBatches($productId)` after any direct mutation. Known direct-mutation drift points: `Admin/IncompleteOrderController.php`, `Admin/RedXWebhookController.php`, `Admin/RefundController.php`, `Admin/WarrantyController.php`, `Admin/OrderController.php`, `Admin/PurchaseController.php`, `Api/Mobile/OrderController.php`.
2. **`ProductController::store/update` always sets `stock = 0`** — stock is only added via purchase batches / stock adjustments. Don't "fix" this by letting the form set stock.
3. **NEVER touch `config/updater.php`** — the license server is hardcoded + base64-encoded; `LicenseService::assertConfigIntegrity()` makes the app refuse to boot if altered.
4. **`DEMO_MODE=true`** in `.env` makes the admin panel read-only (`demo_mode` middleware).
5. **No docker-compose.yml** → Sail won't run; MySQL is often unreachable in sandboxed environments. To validate view edits DB-free, use `php artisan view:clear` + Blade `compileString` via tinker.

## Docs

- `plan.md` — stock-sync plan (Steps 1–2 done, 3–4 pending). **Read before touching stock code.**
- `resources/views/backEnd/media/README.md`