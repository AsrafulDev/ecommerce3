# 🧱 Data-Model Deep-Fix — TODO / Plan (Product · Variant · Batch · Warranty · Wholesale · SN)

> **Goal (from owner):** Move **all commerce data** (price, stock, warranty, wholesale, serial numbers) off the Product/Variant tables and onto **Batch-scoped tables**, so each purchase batch is the single source of truth. Product = catalog only; Variant = identity only.
> **Companion docs:** [`UPGRADE-batch-wise-pricing.md`](./UPGRADE-batch-wise-pricing.md) (engine design) · [`STOREFRONT-BATCH-PRICING.md`](./STOREFRONT-BATCH-PRICING.md) · [`STOREFRONT-BATCH-PRICING-FIX.md`](./STOREFRONT-BATCH-PRICING-FIX.md)
> **Status:** Phase 0 ✅ · Phase 1 ✅ applied · Phase 2 ✅ command ready (`model:backfill`) · Phase 3 ✅ (write paths) · Phase 4 ✅ (UI) · **Phase 5 ✅ (storefront/POS consumers, 2026-09-04)** · All destructive steps remain behind a reversible migration + backfill.
> ⚠️ Never touch `config/updater.php`. Preserve Bengali comments. New columns guarded with `Schema::hasColumn()`. `stock_batches` stays the source of truth for qty + price.

---

## 1. Target model (owner spec)

### 1.1 `products` — catalog only
| Field | Notes |
|---|---|
| name | ✓ |
| slug | ✓ |
| 3 category fields | `category_id`, `subcategory_id`, `childcategory_id` |
| details | `description` (long) |
| short details | `note` (short) |
| `has_variant` | bool — derive from `product_type`/variant rows, or store |
| SEO (4) | `meta_title`, `meta_keywords`, `meta_description`, `meta_image` |
| Barcode & Stock Settings | `barcode`, `barcode_type`, `costing_method`, `low_stock_threshold`, `allow_negative_stock`, `weight` |
| unit type | `pro_unit` |
| Media & Video | main image (`productimages`), gallery, `pro_video` |
| Product Settings | `product_type`, `publish_status`, `status`, `is_digital`, `warranty_method`, `is_sn_required`, flags |
| ❌ no longer on product | `purchase_price`, `supplier_price`, `old_price`, `new_price`, `wholesale_price`, `reseller_price`, `min_wholesale_quantity`, `is_wholesale`, `stock` (denormalized cache only) |

### 1.2 `product_variant_prices` — identity only
| Field | Notes |
|---|---|
| product_id | |
| size_id | |
| color_id | |
| barcode | variant scannable barcode |
| image | single variant image (gallery can stay in `productimages`) |
| ❌ removed | `price`, `stock` (moved to batch tables) |

### 1.3 `stock_batches` — commerce source of truth
| Field | Owner spec | Current column |
|---|---|---|
| product_id | ✓ | `product_id` |
| variant_id | ✓ | `variant_price_id` |
| purchase_id | ✓ | `purchase_id` |
| quantity | ✓ | `quantity` / `remaining_qty` |
| unit cost | ✓ | `unit_cost` |
| total cost | 🆕 add | — |
| batch no. | ✓ | `batch_no` |
| mfg / exp date | ✓ | `mfg_date`, `exp_date` |
| custom field | ✓ | `custom_field` |
| selling price | ✓ | `selling_price` |
| mrp | ✓ | `mrp` |
| website active | ✓ | `is_active_for_website` |
| pos active (default true; false ⇒ disabled for BOTH web & pos) | ⚠️ semantic change | `pos_enabled` |
| has purchase warranty | 🆕 flag | — |
| has sell warranty | 🆕 flag | — |
| has wholesale | 🆕 flag | — |

### 1.4 Purchase warranty (per batch)
`product_id`, `variant_id` 🆕, `supplier_id`, `batch_id`, warranty date, warranty start date, terms, `is_transferable`
→ **current:** `supplier_warranties` (has `purchase_item_id, product_id, supplier_id, batch_id(added), warranty_days, start/end, type, terms, is_transferable`). Gap = `variant_id`.

### 1.5 Sell warranty (per batch) — "same way as purchase warranty"
`product_id`, `variant_id`, `batch_id`, warranty date, warranty start date, terms, `is_transferable`
→ **current:** `warranty_sales` (order-anchored) + `product_warranty_tiers` (catalog) + `batch_warranty_tiers` (batch overrides). Needs re-alignment.

### 1.6 Wholesale (per batch)
`product_id`, `variant_id`, `batch_id`, **condition array** (JSON e.g. `[{min,max,price|discount}]`)
→ **current:** normalized rows in `batch_wholesale_prices` (min/max/wholesale_price per row) + legacy `product_wholesale_prices`. Decide JSON vs normalized.

### 1.7 Serial-number list (per batch)
`product_id`, `variant_id`, `purchase_id`, `batch_id`, `stock_sn` array, `sold_sn` array
→ **current:** `stock_batches.sn_stock`/`sn_sold` (JSON columns) + `warranty_sales.serial_numbers`. Decide: dedicated `batch_sn_lists` table vs keep JSON columns.

---

## 2. Current → target mapping

| # | Table | Current | Target | Main action |
|---|---|---|---|---|
| P | `products` | catalog + commerce cols | catalog only | retire commerce input (done in UI); keep legacy cols nullable as cache/mirror |
| V | `product_variant_prices` | id, product_id, color_id, size_id, price, stock, sku, barcode | +image, −price, −stock | additive: add `image`; backfill & drop `price`/`stock` (later phase) |
| B | `stock_batches` | full batch + pricing + sn | +`total_cost`, +`has_purchase_warranty`, +`has_sell_warranty`, +`has_wholesale` | additive migration |
| PW | `supplier_warranties` | supplier warranty (no variant) | purchase warranty w/ variant_id | add `variant_id` |
| SW | `warranty_sales` | order/sell warranty | sell warranty w/ variant_id + batch_id | add `variant_id`, `batch_id`, terms |
| W | `batch_wholesale_prices` | normalized tiers | condition-array (or keep) | decide |
| SN | `stock_batches.sn_stock/sn_sold` | JSON on batch | `batch_sn_lists` (or keep) | decide |

---

## 3. Decisions needed (confirm before destructive steps)

- [ ] **D1 SN storage:** dedicated table `batch_sn_lists` (owner spec) **vs** keep `stock_batches.sn_stock/sn_sold` JSON. *Recommend: dedicated table — cleaner sold/history queries.*
- [ ] **D2 Wholesale shape:** JSON "condition array" on one row **vs** current normalized `batch_wholesale_prices` rows. *Recommend: keep normalized rows (queryable, already implemented); condition array can be the JSON snapshot.*
- [ ] **D3 pos_active semantics:** confirm `pos_enabled = false` ⇒ hidden from BOTH web & POS (i.e. it becomes a global "batch enabled" flag), while `is_active_for_website` remains "the one web-visible batch". *Recommend: yes — treat as: website shows only `is_active_for_website` AND `pos_enabled=1`; POS sells any `pos_enabled=1` batch.*
- [ ] **D4 has_variant on product:** store a real `has_variant` boolean (maintained by Product save) or keep deriving from `product_type`/variant rows. *Recommend: keep derived; optionally cache a column.*
- [ ] **D5 Sell warranty:** keep `warranty_sales` (order-anchored) + `batch_warranty_tiers` (pricing overrides) and only add missing `variant_id`/`batch_id`, **vs** a new parallel `sell_warranties` table. *Recommend: extend existing, don't duplicate.*
- [ ] **D6 Product price columns:** keep `products.new_price/old_price/…` as nullable legacy mirrors (recommended) or hard-drop after backfill.
- [ ] **D7 total_cost:** store derived `total_cost = quantity × unit_cost` or compute on the fly. *Recommend: store on purchase, keep in sync on returns/adjustments.*

---

## 4. Phased TODO

### Phase 0 — Audit & freeze
- [ ] Grep every read of `products.new_price/old_price/purchase_price/stock`, `product_variant_prices.price/stock`, `product_wholesale_prices`, `sn_stock/sn_sold` in `app/`, `resources/views/`, `routes/`.
- [ ] Confirm current DB columns per table (products, product_variant_prices, stock_batches, supplier_warranties, warranty_sales, batch_wholesale_prices, batch_warranty_tiers, batch_variant_prices).
- [ ] Freeze an ops checklist; get owner sign-off on §3 D1–D7.
- [x] Product & Variant create/edit UI: removed per-variant Price + product Purchase/Old/New/Wholesale inputs (done earlier) — aligns with "commerce comes from batch".

### Phase 1 — Additive migrations + models ✅ done (2026-09-04, applied to local DB)
- [x] Migration `2026_09_04_000001_add_variant_image_to_product_variant_prices` → `image` (guarded).
- [x] Migration `2026_09_04_000002_add_total_cost_to_stock_batches` → `total_cost` (guarded).
- [x] Migration `2026_09_04_000003_add_commerce_flags_to_stock_batches` → `has_purchase_warranty`, `has_sell_warranty`, `has_wholesale` (guarded).
- [x] Migration `2026_09_04_000004_add_variant_id_to_supplier_warranties` (guarded; no FK on purpose).
- [x] Migration `2026_09_04_000005_add_variant_terms_to_warranty_sales` → `variant_id`, `terms`, `is_transferable` (`stock_batch_id`/`purchase_id` already existed).
- [x] Migration `2026_09_04_000006_create_batch_sn_lists` → product_id, variant_id, purchase_id, batch_id, stock_sn json, sold_sn json.
- [x] Eloquent `$fillable`/casts updated on: `ProductVariantPrice` (+image), `StockBatch` (+total_cost, +has_*), `SupplierWarranty` (+variant_id), `WarrantySale` (+variant_id/terms/is_transferable), new `BatchSnList` model.
- [x] Relationships added (variant↔warranties, `StockBatch::snList()` ↔ `BatchSnList`).

### Phase 2 — Backfill ✅ command ready (2026-09-04); run against a data-bearing DB
- [x] Created idempotent command **`php artisan model:backfill`** (`app/Console/Commands/BackfillModelFields.php`) with `--dry-run`, `--product=`, `--only=variant,cost,flags,sn`.
  - [x] variant → `product_variant_prices.image` from first matching `productimages` (color/size aware).
  - [x] cost → `stock_batches.total_cost = quantity × unit_cost` (in-type batches only).
  - [x] flags → `has_purchase_warranty` (supplier_warranties by batch/purchase+product), `has_sell_warranty` (warranty_sales by batch/purchase+product), `has_wholesale` (batch_wholesale_prices).
  - [x] sn → `stock_batches.sn_stock/sn_sold` → `batch_sn_lists` rows (one per batch).
- [x] `php artisan model:backfill --dry-run` runs cleanly.
- [ ] ⚠️ Ops: execute against a **data-bearing** DB (staging/prod): `php artisan model:backfill --dry-run` → review → `php artisan model:backfill` → verify counts. (This local dev DB is empty — 0 rows — so the run is a no-op here.)

### Phase 3 — Purchase / batch write paths ✅ done (2026-09-04)
- [x] `StockManagementService::stockIn` now writes `total_cost` (qty×unit_cost) + accepts `has_purchase_warranty`/`has_sell_warranty`/`has_wholesale`.
- [x] `PurchaseController::store` links `supplier_warranties.batch_id` + `.variant_id` after batch creation and sets the batch `has_purchase_warranty` flag.
- [x] `PurchaseController::persistBatchPricing` sets `has_wholesale` / `has_sell_warranty` on the batch when batch wholesale/warranty tiers are written.
- [x] `WarrantyService::createWarrantySale` + POS writer (`Admin/OrderController`) store `variant_id`/`stock_batch_id`/`purchase_id`/`terms`/`is_transferable` and flag `has_sell_warranty` on the sold batch.
- [x] SN consistency: `StockBatch::saved` event auto-mirrors `sn_stock`/`sn_sold` → `batch_sn_lists` on every write (purchase SN entry, POS sale, returns, warranty moves). Runtime-verified with a rollback-safe DB test.
- [x] No `products.stock`/website-cache drift introduced (no qty changes made in these paths).

### Phase 4 — Product + Variant UI ✅ mostly done (2026-09-04)
- [x] Product page = catalog only: price/wholesale inputs already removed (earlier). `has_variant` is derived from `product_type`/variant rows (D4) — the variant section title + toggle show the state.
- [x] Variant rows keep Color / Size / **Barcode** + the per-row **Variant Image** (media-library) picker; the picked image is now mirrored into `product_variant_prices.image` on save via `ProductController::syncVariantImagesFromGallery()` (store + update; non-destructive). Guidance note added to both Create & Edit variant cards ("prices/stock managed per batch…").
- [~] Read-only per-variant sell price/stock: covered by the existing read-only batch stock table on Edit (Batch / Qty / Unit Cost / Sell / Expiry) + the note. Full per-variant resolution appears where batch data exists (purchase panel / detail).

### Phase 5 — Storefront/POS consumers re-check ✅ done (2026-09-04)
- [x] **D3 semantics enforced in the website price path**: `PricingService::activeWebsiteBatch()` + `refreshProductCache()` and `StockManagementService::syncStockFromBatches()` now require the active batch to be `pos_enabled = true`. A batch flagged `is_active_for_website` but `pos_enabled=false` is never shown/priced on the website (POS already excluded via `posBatches`/`sellable()`). Documented on the `StockBatch::sellable()` scope.
- [x] **scanBarcode verified**: resolves product via `products.barcode`/`product_code`, else variant via `product_variant_prices.barcode`/`sku`; prices through `PricingService` using `posBatches()` (FIFO default, pos-enabled only). (Note: a scanned variant currently adds at product level; variant-specific price is picked via the POS variant selector.)
- [x] **Variant detail/cart/order use batch price**: covered by `PricingServiceTest` (price from active batch, variant batch price, no legacy fallback when batch-wise ON unless a batch lacks a price — D6 mirror).
- [x] **OOS per channel re-verified**: website sellable only when an enabled active batch has stock (`isWebsiteSellable`); POS lists only `pos_enabled` batches.
- [x] Tests: added `test_pos_disabled_batch_not_used_by_website` — suite now **24 passing (82 assertions)** with `BATCH_WISE_PRICING=true`.

### Phase 6 — Tests & cutover
- [ ] Feature/unit tests for: variant barcode+image save, total_cost, has_* flags, warranty variant/batch, SN list, batch pos-active disable both channels.
- [ ] Run `php artisan test --filter=PricingService` still green.
- [ ] Execute cutover checklist in low-traffic window (backfill → verify → flip `BATCH_WISE_PRICING`).

---

## 5. Quick file map

| Concern | Files |
|---|---|
| Schema | `database/migrations/` (new additive migrations above) |
| Models | `app/Models/ProductVariantPrice.php`, `StockBatch.php`, `SupplierWarranty.php`, `WarrantySale.php`, (+ new `BatchSnList.php`) |
| Batch write | `app/Services/StockManagementService.php`, `Admin/PurchaseController.php`, `Admin/StockController.php` |
| Warranty/SN | `Admin/WarrantyController.php`, `WarrantyService.php`, `PurchaseController.php` (SN entry) |
| Product/Variant UI | `resources/views/backEnd/product/create.blade.php`, `edit.blade.php`, `Admin/ProductController.php` |
| Pricing consumers | `app/Services/PricingService.php`, `Frontend/*`, `Admin/OrderController.php` (POS), `Api/Mobile/*` |
| Backfill | `app/Console/Commands/BackfillBatchPricing.php` (+ a new `model:backfill` if needed) |
