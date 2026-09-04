# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> An `AGENTS.md` also exists at the repo root with equivalent guidance — keep the two in sync if you update one.

## Project

Laravel 12 e-commerce platform ("Ecommerce Pro") with a full admin panel, storefront, POS, stock/batch management, and a warranty pipeline, plus a mobile (Flutter) API. A paired WordPress plugin, `softmit-license-manager`, acts as its license/update server.

## Commands

- **Run tests:** `php artisan test` (Unit + Feature suites)
- **Run a single test:** `php artisan test --filter=TestClassName` or `php artisan test tests/Feature/PricingServiceTest.php`
- **Frontend build:** `npm run dev` / `npm run build` — Vite, but it only covers `resources/sass` + `resources/js`; the actual admin/storefront UI is static assets under `public/backEnd` and `public/frontEnd`, not part of the Vite pipeline
- **PHP version:** `^8.2` (composer platform pinned to `8.2.12`)
- **Sail is not usable** — there is no `docker-compose.yml`
- **Custom Artisan commands** (auto-discovered, no `Kernel` registration needed — see `app/Console/Commands/`):
  - `stock:sync-from-batches {--product=} {--dry-run}` — reconcile `products.stock` from `stock_batches`
  - `warranty:expire`, `warranty:update-tiers`
  - `courier:check-status {--limit=50} {--force}` — poll Pathao/Steadfast/RedX for status updates
  - `update:release {version} {--upload} {--secret=}` — build/upload an update zip to the license server
  - `migrate:fresh:default` — fresh migrate + default seeder only (see `DefaultDatabaseSeeder`)

## Architecture

- **Models** (`app/Models/`, ~100 files): `Product`, `ProductVariantPrice`, `StockBatch`, `StockAdjustment`, `Order`, `Customer`, `Warranty*`, `Version`, `Courierapi`, `Employee*`, etc. Many use `$guarded = []`; Laravel 12 `casts()` method (not the `$casts` property); `Product` uses slug route-key binding.
- **Services** (`app/Services/`): fat controllers delegate business logic here, injected via constructor or resolved ad hoc (`app(StockManagementService::class)`).
  - `StockManagementService` — `stockIn`/`stockOut`/`adjustStock`/`syncStockFromBatches`, FIFO/LIFO/average costing
  - `PricingService`, `OrderStatusService`, `DuplicateOrderService` — order/pricing pipeline
  - `WarrantyService`, `WarrantyPriceCalculator`, `WarrantyDisplayService`, `WarrantyChallanService`, `CartWarrantyService` — warranty pipeline
  - `LicenseService` — license/update-server integration (see pitfall #3 below)
  - `RedXService`, courier integrations; `FacebookCapiService`, `FacebookPagePostService`, `Ads/` (Facebook/Google/TikTok ads services)
- **Enums** (`app/Enums/`): `OrderStatus`, `PaymentStatus`, `Warranty*`, `Damage*`. Order status is enum-driven — `OrderStatus::fromLegacyId()` bridges old integer status columns.
- **Helpers**: `app/helpers.php` (autoloaded via composer `files`) provides `log_activity()`, color helpers, etc. `app/Helpers/`: `FundHelper`, `OrderHelper`, `PresetData`.
- **Routes**: `routes/web.php` (admin routes under `prefix('admin')`, middleware `['auth:admin','admin','lock','check_refer','demo_mode']`); `routes/api.php` (`v1` public, `v1/mobile` Sanctum-protected for the Flutter app, `updates` license-protected). Middleware aliases are registered in `bootstrap/app.php`, not a Kernel class (Laravel 12 style).
- **Views**: admin UI in `resources/views/backEnd/**` (layout `backEnd/layouts/master.blade.php`); storefront in `resources/views/frontEnd/**`. The customer account panel is a separate Tailwind layout that does **not** load the product-card CSS used elsewhere.

## Conventions

- **Permissions**: Spatie `laravel-permission`, gated in controller constructors, e.g. `$this->middleware('permission:product-list|product-create', ['only' => [...]]);`
- **Flash messages**: `Toastr` facade.
- **Audit logging**: `log_activity($module, $action, $description, $model, $data)`.
- **Defensive DB checks**: `Schema::hasColumn()` guards code paths against columns that may be missing on live/older databases.
- Comments are a mix of Bengali and English — preserve both when editing nearby code.

## Critical pitfalls

1. **Stock drift.** `products.stock` is a denormalized copy; the source of truth is `stock_batches.remaining_qty`. Always mutate stock through `StockManagementService::stockIn/stockOut/adjustStock`, or call `syncStockFromBatches($productId)` after any direct mutation. Known direct-mutation drift points to watch: `Admin/IncompleteOrderController.php`, `Admin/RedXWebhookController.php`, `Admin/RefundController.php`, `Admin/WarrantyController.php`, `Admin/OrderController.php`, `Admin/PurchaseController.php`, `Api/Mobile/OrderController.php`. See `plan.md` before touching stock code.
2. **`ProductController::store/update` always sets `stock = 0`** — this is intentional; stock is only ever added via purchase batches or stock adjustments. Don't "fix" this by letting the product form set stock directly.
3. **Never touch `config/updater.php`.** The license server address is hardcoded and base64-encoded; `LicenseService::assertConfigIntegrity()` makes the app refuse to boot if it's altered.
4. **`DEMO_MODE=true`** in `.env` makes the admin panel read-only via the `demo_mode` middleware.
5. **No `docker-compose.yml`** → Sail won't run, and MySQL is often unreachable in sandboxed environments. To validate a Blade view edit without a DB connection, use `php artisan view:clear` plus Blade's `compileString` via `php artisan tinker`.

## Docs

Root-level docs worth reading before touching related areas (not duplicated here):
- `plan.md` — stock-sync plan (read before touching stock code)
- `DATABASE.md` — schema reference
- `PRODUCT-LIFECYCLE.md`, `SERVICES-FEATURES.md` — product/order domain notes
- `STOREFRONT-BATCH-PRICING.md`, `STOREFRONT-BATCH-PRICING-FIX.md`, `UPGRADE-batch-wise-pricing.md` — batch-wise storefront pricing feature history
- `DATA-MODEL-REFACTOR-TODO.md`, `UPDATE-PLAN.md`, `CUTOVER-CHECKLIST.md` — in-flight refactor tracking
- `resources/views/backEnd/media/README.md`
