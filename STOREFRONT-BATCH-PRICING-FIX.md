# Storefront Batch Pricing — Fix & Update Plan ("make it work")

> **Applies to:** `lara` storefront (frontEnd)
> **Companion doc:** [`STOREFRONT-BATCH-PRICING.md`](./STOREFRONT-BATCH-PRICING.md) — the target design ("proper way").
> **Reading order:** this plan fixes the live bug (storefront shows `products.new_price` instead of batch prices) in phases; each phase is independently shippable and testable. Run phases in order.
> **Source spec:** "E-Commerce Batch, Variant, Pricing & Cart Allocation Specification" — **[Spec §N]**.

---

## ✅ Implementation status (2026-09-03)

| Phase | Title | Status |
|---|---|---|
| **0** | Preconditions & data truth | 🔜 Pending (do before going live: backfill `selling_price`/`mrp`, `stock:sync-from-batches`, flip `BATCH_WISE_PRICING`) |
| **1** | PricingService (range & allocation) | ✅ **Done** — see 1.1–1.7 checked below |
| **2** | Catalog queries stop trusting `new_price` | ✅ **Done** — 2.1 (display via attached ranges), 2.2 (sort), 2.3 (slider/filter), 2.4 (`attachCatalogRanges`) all implemented |
| **3** | Product card + partials render the range | ✅ **Mostly done** — 3.1, 3.2, 3.4 done; 3.3 done except **campaign** view |
| **4** | Product detail page (variant-aware) | ✅ **Done** — 4.1 range text, 4.2 payload, 4.3 per-variant stock/OOS, controller cache-proof all implemented; 4.4 is a minor fallback-only item |
| **5** | Cart batch-split lines | ✅ **Done (opt-in)** — `PRICING_MULTI_BATCH_PRICING=per_batch`: money-exact weighted billing + reprice on qty change; default `active_batch` unchanged |
| **6** | Checkout & order snapshot | 🟡 Mostly — **6.1** per-batch snapshot, **6.2** Σ-billing (per_batch), **6.3** deduction==priced (per_batch), **6.4** locking all done; only 6.5 (invoice batch breakdown) pending |
| **7** | POS + other batch users | 🟡 Verified — POS `cart_add`/`scanBarcode`/`cart_update` already price via `PricingService` (batch-wise); sweep other `new_price` reads (7.2) |
| **8** | Tests & cutover | 🟡 In progress — `tests/Feature/PricingServiceTest.php` **22 passing (75 assertions)**; T1/T3/T4/T5/T6/T7 + LIFO/AVG + min-sale join + weighted-unit covered |

> Everything below is checked off **only** when its item is actually implemented & verified.



## Phase 0 — Preconditions & data truth (no code)

Before any display fix, make sure the **data** is correct, otherwise you will "fix" the code and still see wrong numbers.

- [ ] 0.1 Confirm batch rows actually carry prices.
  ```sql
  SELECT id, product_id, batch_no, remaining_qty, unit_cost, selling_price, mrp,
         is_active_for_website, pos_enabled, exp_date
  FROM stock_batches
  WHERE remaining_qty > 0
  ORDER BY product_id, mfg_date;
  ```
  Expected: every sellable row has `selling_price > 0` and `mrp` set (nullable). `selling_price` has historically **not** been set by `PurchaseController::store` — batches carried `unit_cost` only.
- [ ] 0.2 Backfill sell price/MRP for bare batches. **The repo already ships a command for this** — `app/Console/Commands/BackfillBatchPricing.php` (signature `pricing:backfill`):
  ```bash
  php artisan pricing:backfill --dry-run          # report first
  php artisan pricing:backfill --product=21       # one product
  php artisan pricing:backfill                    # everything (also runs stock:sync-from-batches)
  ```
  It idempotently copies legacy `products.new_price`/`old_price` (+ variant/wholesale/warranty) onto the batches and sets the **active website batch** (oldest sellable FIFO) so the storefront has a priced, sellable batch. Manual per-batch edits remain available via the purchases right panel (Batch popup / pricing routes): `admin.purchases.price.batch.save`, `admin.purchases.price.batches.save`, `admin.purchases.price.activate`, `admin.purchases.price.wholesale.save`, `admin.purchases.price.warranty.save`.
- [ ] 0.3 Reconcile stock (`products.stock` source of truth = `SUM(stock_batches.remaining_qty)`):
  ```bash
  php artisan stock:sync-from-batches          # then confirm 0 mismatches (--dry-run first)
  ```
- [ ] 0.4 **Decide the switch.** The whole engine is behind `BATCH_WISE_PRICING` (default `false` in `config/pricing.php`). Verify a staging clone first with:
  ```bash
  BATCH_WISE_PRICING=true php artisan test --filter=PricingService
  ```
  Only after Phases 1–3 are validated set it **true** in the target env (see CUTOVER-CHECKLIST.md).

---

## Phase 1 — PricingService: make it batch-range & allocation capable

File: `app/Services/PricingService.php` (single source of truth).

- [x] 1.1 **Eligible-batch loader (variant-aware)** — replace ad-hoc queries. Honors [Spec §25] + variant applicability [Spec §26]:
  ```php
  public function eligibleBatches(Product $product, ?int $variantId = null): Collection;
  ```
  Rules: `pos_enabled` + `remaining_qty > 0` + expiry + (specific batch OR `is_all_variants`).
- [x] 1.2 **Price range** — stock-aware min/max ([Spec §4/§22/§33]):
  ```php
  public function priceRange(Product $product, ?int $variantId = null): array
  // ['min_sale'=>float,'max_sale'=>float,'min_mrp'=>float|null,'max_mrp'=>float|null,'count'=>int]
  ```
  Sale = batch `selling_price` (or `batch_variant_prices.price` for the variant); MRP = batch `mrp` (or `batch_variant_prices.old_price`). Exclude exhausted/expired/disabled batches.
- [x] 1.3 **General allocation** — extend existing FIFO-only `websiteAllocation()`:
  ```php
  public function allocate(Product $product, int $qty, ?int $variantId = null,
                           string $method = 'fifo'): array
  // [{batch, qty, unit_price, mrp}]  — 'fifo'|'lifo'|'avg'  (see STOREFRONT-BATCH-PRICING.md §6)
  ```
  Keep `websiteAllocation()` as a thin FIFO wrapper so existing callers (order_save) don't break.
- [x] 1.4 **Product-level method** — read `products.allocation_method` (new column, default `FIFO`); do **not** hardcode FIFO.
- [x] 1.5 **Cache sync both columns** — extend `refreshProductCache()` so it also writes the **MRP** (`old_price`) and range helpers from the active batch (today it only writes `selling_price → new_price`). Ensure it is invoked on every stock/price mutation (it already is on activation; check stock-out paths in `CustomerController::order_save` → it calls `refreshProductCache`).

### DB for Phase 1
- [x] 1.6 Migration `add_allocation_method_to_products` → `products.allocation_method` enum('FIFO','LIFO','AVG') default 'FIFO'.
- [x] 1.7 Migration `add_is_all_variants_to_stock_batches` → `stock_batches.is_all_variants` bool default false (guarded with `Schema::hasColumn` per repo convention).
  > Optional/delayed: if you keep current `variant_price_id`-only batches, Phase 1.1 still works — just skip the `is_all_variants` clause until the admin UI writes it. Spec features [Spec §16 Option B] need it; simple FIFO products do not.

---

## Phase 2 — Catalog queries stop trusting `new_price` (THE core fix) ✅ done

> Implemented: **2.1/2.4** display now reads the attached `price_min/max` + `mrp_min/max` attributes (`PricingService::attachCatalogRanges()` — one grouped query per collection, no N+1); **2.2** sorting and **2.3** the price slider/filter operate on each product's **lowest sellable batch sale price** (LEFT JOIN of a per-product `MIN(selling_price)` subquery) — both only when `BATCH_WISE_PRICING` is ON; otherwise the legacy `new_price` path is unchanged. Wired into: `brand`, `shop`, `category`, `subcategory`, `products` (childcategory), `hotdeals`, `flashsales`, `search`, `livesearch`, homepage collections, `details` (related/single), `quickview`.

File: `app/Http/Controllers/Frontend/FrontendController.php`

Today every method does `select('id','name','slug','new_price','old_price','stock')` and sorts/filters on `new_price`:
`index`, `category`, `subcategory`, `childcategory`, `products`, `shop`, `search`, `livesearch`, `details` (related block), plus `campaign`.

- [x] 2.1 **Stop selecting static price as the display value.** Either
  - (a) drop `new_price`/`old_price` from `->select(...)` and eager-load a per-product **range** via one grouped query (`SELECT product_id, MIN(selling_price), MAX(selling_price), MIN(mrp), MAX(mrp) FROM stock_batches WHERE pos_enabled=1 AND remaining_qty>0 AND (exp_date IS NULL OR exp_date>=...) GROUP BY product_id`), or
  - (b) keep using the cached `products.website_price` / add `website_mrp` columns written by `refreshProductCache()`.
  Recommend (a) for correctness; (b) for speed once cache is reliable.
  > ✅ Implemented via approach (a) `attachCatalogRanges()` — display never shows a static single price when batches differ; `new_price`/`old_price` stay only as legacy/JS fallback.
- [x] 2.2 **Sorting** — ✅ `orderBy('bm.min_sale')` (lowest sellable batch price) via `FrontendController::joinBatchMinSale()` + `batchPriceSortColumn()`.
- [x] 2.3 **Price slider / filter** — ✅ min/max computed from `MIN(selling_price)` over the filtered product set; filter applies to `bm.min_sale` (never on an exhausted batch).
- [x] 2.4 **Attach range to each product for the card** — ✅ done via `PricingService::attachCatalogRanges()` (accepts Collection/Paginator/Product; ONE grouped query; sets `price_min`/`price_max`/`mrp_min`/`mrp_max`/`price_single`/`stock_sellable`; legacy fallback). Wired in: `getHomepageData`, `brand`, `shop`, `category`, `subcategory`, `products` (childcategory), `hotdeals`, `flashsales`, `search`, `details` (related + single), `quickview`, `livesearch`.

---

## Phase 3 — Product card + list partials render the range ✅ mostly done

> Implemented: 3.1, 3.2, 3.4. 3.3 done for search/livesearch/quickview — **campaign view still static**.

Files: `resources/views/frontEnd/layouts/sections/product-card.blade.php` (+ styles variants inside it), `product-card-panel.blade.php`, search/livesearch/quickview ajax partials, campaign view, category/subcategory/childcategory pages.

- [x] 3.1 `product-card.blade.php` — ✅ all 6 design families (premium/overlay/ribbon/glass/classic) now render `৳10 - ৳12` / `<del>৳20 - ৳24</del>` from `$pcSaleLabel`/`$pcMrpLabel` (range when attached, static fallback otherwise). Ajax partials `quickview.blade.php` + `ajax/search.blade.php` updated too.
- [x] 3.2 Discount % — ✅ computed from `$pcMrpMax` vs `$pcSaleMax` (guard when no MRP/zero).
- [ ] 3.3 Search / livesearch / quickview / campaign price bits — ✅ search + livesearch + quickview done (batch-aware numeric for CAPI `data-price`); **campaign page price bits still static**.
- [x] 3.4 Validate DB-free in sandbox — ✅ all touched templates Blade-compile (`view:clear` + `compileString`).

---

## Phase 4 — Product detail page (variant-aware, no reload) ✅ done

> Implemented (extra, controller-side): `FrontendController::details()` now calls `attachCatalogRanges($details)` and mirrors the resolved **active-website-batch** sale/MRP into the model's `new_price`/`old_price` attributes (view-only) — so the header price, discount badge and all ~16 JS `new_price` reads are batch-accurate and no longer stale from the 10-min `product_details_*` cache.
> Implemented (4.2/4.3): `details()` also builds a **per-variant batch availability map** (`variant_price_id → {sale, mrp, stock}` from `eligibleBatches()` + `priceRange()`); the view injects it as `bpAvailability`, repoints each variant's displayed price to its own batch price, and **disables Add-to-Cart / shows "out of stock"** when the selected combination has no eligible batch stock.
> **Minor leftover:** 4.4 — the server-side `variantPrices[].price` attribute override still falls back to the active batch (the JS already shows the per-variant eligible-batch price via `bpAvailability`). 4.5 (resolver allocation on add-to-cart) is covered by Phase 5 (`cartStore` allocates).
> **Server-side variant enforcement added (2026-09-03):** a specific batch (`variant_price_id` set, not ALL) now serves ONLY its own variant — `batch_variant_prices` rows no longer make it eligible for other variants (fixed in `PricingService::eligibleBatches()`). `cartStore` rejects combos with no eligible batch stock and invalid color/size combinations.
> **Client-side (2026-09-03):** the details page disables Add-to-Cart the moment a combo is chosen that is out of stock **or doesn't match a real variant segment** (toast differs for invalid vs OOS). Verified live on product #2: Red+S → enabled; Red+M & Maroon+S → disabled (invalid segment); Maroon+M → disabled (OOS).

File: `resources/views/frontEnd/layouts/pages/details.blade.php` (+ `FrontendController::details`).

- [x] 4.1 Before variant selection show the **range** ([Spec §23]): `MRP: 20 - 24` / `Price: 10 - 14`. ✅ Header now renders `৳min - ৳max` (with `<del>` MRP range) when batches differ; JS keeps the range visible until a variant is chosen (`data-range` guard).
- [x] 4.2 Emit a JSON payload (from `priceRange()` + per-combination `eligibleBatches`) so JS can recompute stock/price/MRP on variant change ([Spec §39]). ✅ `details()` builds `$variantAvailability`; view consumes it via `bpAvailability`.
- [x] 4.3 On variant select: stock = sum over applicable batches; price = applicable sale; disabled when no eligible batch ([Spec §18/§39]). ✅ `updateVariantStockUI()` disables `.add_cart_btn`/`.order_now_btn` when the chosen variant's batch stock ≤ 0; variant price comes from its own eligible batch.
- [ ] 4.4 `details()` controller: keep `isWebsiteSellable`/`activeBatch` (used by stock-out) but stop overriding `variantPrices[].price` from the *active* batch only; base it on **eligible batches** for that variant. 🟡 Minor: JS already shows per-variant eligible-batch prices (`bpAvailability`); the attribute override still uses the active batch as fallback.
- [ ] 4.5 Add-to-cart posts variant ids + qty → the resolver returns the allocation (see Phase 5).

---

## Phase 5 — Cart: batch-split lines ✅ done (opt-in behind `per_batch`) ([Spec §27])

> ⭐ Implemented behind `config('pricing.multi_batch_pricing')` — **default stays `active_batch`** (zero behaviour change). When set to **`per_batch`** (with `BATCH_WISE_PRICING=true`):
> - **Add to cart** bills the **quantity-weighted average** of the allocated batches (`PricingService::weightedAllocationUnit()`), so the line total equals **Σ(qty_i × price_i)** exactly (T1: 3×10 + 17×12 = 234, not 20×12) while keeping ONE cart row (no duplicate display rows).
> - **Qty change** (increment/decrement) always **reprices** the row for the new quantity via `ShoppingController::repriceCartRow()` — it re-derives the base unit (active-batch price, or the qty-weighted average under `per_batch`) **and re-resolves the batch wholesale quantity-discount + warranty for the new qty**. This fixes the case where dropping below a wholesale tier (e.g. 2–5 pcs → 1 pc) kept the discounted unit price.
> - Per-batch record is preserved at checkout by the Phase 6.1 order snapshot.
> **Deviation note (D3):** spec's "separate internal lines per batch" is implemented as a single weighted-unit line (identical money total, simpler UI/invoice). Switching to visually separate per-batch lines can be layered on later via the same `weightedAllocationUnit`/allocation data.
> **Scope:** `cart_update` (color/size change) and `changeProduct` keep legacy pricing (not re-priced) — documented, low usage.

Files: `FrontendController::cartStore`, `Frontend/ShoppingController` (cart_show / cart_add / increments / decrements / cart_update / sidebar), cart ajax partials (`cart_sidebar.blade.php`, cart show).

- [x] 5.1 **Add to cart = allocate.** ✅ `cartStore` allocates via `allocate()` and prices at the weighted per-unit of the eligible batches (`weightedAllocationUnit()`).
- [x] 5.2 **Price correctness invariant:** total = Σ per-batch `qty × unit` (e.g. `3×10 + 17×12 = 234`) ✅ — covered by `test_weighted_allocation_unit_preserves_per_batch_total`.
- [x] 5.3 **Cart qty change re-prices (always on).** ✅ `cart_increment`/`cart_decrement` call `repriceCartRow()` — re-derives base + wholesale tier + warranty for the new qty in every mode (not just `per_batch`); falls back gracefully when out of stock.
- [ ] 5.4 Cart summary/sidebar show per-batch detail internally — 🟡 not rendered as separate lines (single weighted row). Optional enhancement.

---

## Phase 6 — Checkout & order snapshot 🟡 partial ([Spec §28–30])

> Implemented: **6.1** — storefront `CustomerController::order_save` now persists a **per-batch snapshot** on each `order_details.batch_ids` (array cast): for every FIFO portion it stores `stockOut()`'s `batch_details` (`batch_id`, `qty`, `unit_cost`, `cogs`) **plus** `unit_price` (that batch's sale price at order time). Same shape admin/refund flows already read (`normalizeBatchEntries`). Billing stays single active-batch price (policy `active_batch`) until Phase 5/D3.

Files: `app/Http/Controllers/Frontend/CustomerController.php` (`order_save`), `app/Helpers/OrderHelper.php` (`saveOrderDetails`), `app/Models/OrderDetails.php`.

- [x] 6.1 **Snapshot per batch.** ✅ `order_details.batch_ids` now stores the rich per-batch allocation (batch_id, batch_no via batch lookup, qty, unit_cost, cogs, unit_price) for batch-wise storefront orders — history survives later batch price changes. (Admin/mobile paths already did this via `stockOut()`.)
  ```php
  // batch_ids becomes: [{batch_id, batch_no, qty, unit_price, mrp,
  //                      wholesale_discount, warranty_adjustment}]
  ```
  Order line's own `sale_price` stays as the **weighted/representative** price for legacy views/invoices.
- [x] 6.2 **Price at checkout** = Σ allocation ✅ — under `PRICING_MULTI_BATCH_PRICING=per_batch` the cart line is billed at the weighted unit (`weightedAllocationUnit`), so the checkout total = Σ(qtyᵢ × priceᵢ) automatically. (Default `active_batch` still bills the single active-batch price — policy D3.)
- [x] 6.3 **Deduct exact batches** ✅ — deduction is per-batch FIFO, row-locked (6.4), and snapshotted (6.1); under `per_batch` the billed allocation (Σ of weighted unit) equals the deducted allocation. Under the default `active_batch` policy billing remains the active-batch price while deduction is FIFO (accepted policy, decision D3).
- [x] 6.4 **Concurrency / oversell** — lock the stock-batch rows (`lockForUpdate`) before allocating in `order_save`, then commit ([Spec §30]). ✅ `CustomerController::order_save` batch-wise branch now holds `lockForUpdate` on the product's sellable batches (inside the existing transaction) before FIFO allocation, so simultaneous orders for the same batches serialize. (`stockOut` asserts remaining stock inside the same locked section.)
- [ ] 6.5 Order success / invoice / tracking / refund / PDF views currently re-derive `sale_price` from the order line — keep them working off the snapshot columns; only add batch breakdown where required.

---

## Phase 7 — POS + other batch users 🟡 verified (mostly done)

> Verified 2026-09-03: the POS add paths **already** route through `PricingService` in batch-wise mode — `Admin/OrderController::cart_add`, `scanBarcode` and `cart_update` all resolve the sell price via `$pricing->price($product, $batchId, $variantId, 'pos')` (+ `mrp`, batch `unit_cost`, batch warranty). Remaining: 7.2 sweep of any other live reads of `new_price`/`stock` used as a *sellable/price* decision.

Files: `app/Http/Controllers/Admin/OrderController.php`, `Api/Mobile/*`, warranty/refund paths.

- [x] 7.1 POS intentionally sells **per chosen batch** — keep that, but route its pricing through the same resolver so a batch's sell price is identical to storefront. ✅ Verified: `cart_add`/`scanBarcode`/`cart_update` already call `PricingService::price(... 'pos')` + `mrp` + batch `unit_cost` + batch warranty in batch-wise mode.
- [ ] 7.2 Anywhere `products.stock` / `new_price` is still read for a *sellable* decision, replace with `PricingService::sellableStock()` / `priceRange()` (search for `->new_price`, `->old_price`, `->stock` in `app/Http`, `resources/views/frontEnd`).
- [ ] 7.3 Keep `plan.md` stock-drift rules: all mutations through `StockManagementService`, then `refreshProductCache()`.

---

## Phase 8 — Tests & cutover 🟡 in progress

- [ ] 8.1 Encode the spec acceptance tests ([Spec §40]) as Feature/Unit tests: `tests/Feature/BatchPricingTest.php` covering T1–T7 (see STOREFRONT-BATCH-PRICING.md §13).
  > 🟡 Added to `tests/Feature/PricingServiceTest.php` instead of a new file (same suite): T1 (FIFO split pricing), T3 (range), T4 (exhausted-batch), T5 (no batch → OOS), T6 (ALL fallback), T7 (specific > ALL), plus LIFO, AVG weighted, product default method, `attachCatalogRanges`, the catalog min-sale join, `weightedAllocationUnit`, and the specific-batch-vs-bvp eligibility rule. **23 tests passing (78 assertions).**
- [x] 8.2 Run: `php artisan test --filter=PricingServiceTest` — ✅ **23 passed (78 assertions)**; original suite (8) still green.
- [ ] 8.3 Execute the ops checklist from `CUTOVER-CHECKLIST.md` in a low-traffic window (set `BATCH_WISE_PRICING=true`, clear caches, run `stock:sync-from-batches`, spot-check a 3-batch product card/detail/cart/order).
- [ ] 8.4 Watch for the classic regressions: exhausted batch still showing low price (T4), variant fallback picking ALL batch when specific exists (T7), cart total ≠ Σ(batch lines), order deducting a different batch than it charged.

---

## Quick file map (what to touch, grouped)

| Concern | Files |
|---|---|
| Pricing math / allocation / range | `app/Services/PricingService.php` |
| Catalog price queries, sort, filter | `app/Http/Controllers/Frontend/FrontendController.php` |
| Product model accessors | `app/Models/Product.php`, `app/Models/StockBatch.php` |
| Schema | `database/migrations/` (allocation_method, is_all_variants) |
| Cards / lists / search | `resources/views/frontEnd/layouts/sections/product-card*.blade.php`, ajax/search, quickview, category/subcategory/childcategory, campaign |
| Detail page + JS variant recalc | `resources/views/frontEnd/layouts/pages/details.blade.php`, `FrontendController::details` |
| Cart split lines | `FrontendController::cartStore`, `Frontend/ShoppingController.php`, cart partials |
| Order snapshot | `CustomerController::order_save`, `app/Helpers/OrderHelper.php`, `OrderDetails` |
| Config / flags | `config/pricing.php` (+ `.env` `BATCH_WISE_PRICING`, `PRICING_MULTI_BATCH_PRICING`) |

> ⚠️ Never touch `config/updater.php`. Preserve Bengali comments. Use `Schema::hasColumn()` guards on new columns. Keep `stock_batches` as the source of truth for qty and price.
