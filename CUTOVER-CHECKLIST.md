# 🔄 Cutover & Rollback Checklist — Batch-Wise Pricing Engine

> Companion to `UPGRADE-batch-wise-pricing.md`. Use this when enabling `BATCH_WISE_PRICING=true` in production. Everything is **flag-gated** — setting the flag is the only irreversible-ish step, and even that rolls back by clearing the flag. Read top to bottom; do not skip the pre-flight.

---

## 0. Timeline / Ownership
- [ ] **Scheduled window:** low-traffic hours (e.g. after midnight). Expected duration: ~30–60 min.
- [ ] **Owner:** a senior admin who can edit `.env`, run artisan, and use phpMyAdmin/MySQL client.
- [ ] **Rollback owner + how to reach them** noted (SMS/phone).

---

## 1. Pre-flight (day before — read-only, no risk)

- [ ] Confirm PHP version + MySQL version:
  ```bash
  php -v
  mysql --version          # must be any MySQL 5.7+/8.x (partial index NOT used, so fine)
  php -m | grep pdo_mysql
  ```
- [ ] Create the **test database** and run the suite (so the service layer is proven):
  ```bash
  mysql -u root -e "CREATE DATABASE IF NOT EXISTS ecommerce3_test"
  php artisan test --filter=PricingServiceTest
  ```
- [ ] **Backup** (mandatory):
  ```bash
  mysqldump -u root ecommerce3 > ecommerce3_pre_batch_$(date +%F).sql
  tar -czf public_uploads_pre_batch_$(date +%F).tgz public/uploads
  ```
  Store both off-server.
- [ ] Confirm the migrations are already applied and idempotent:
  ```bash
  php artisan migrate:status | grep 2026_08_25
  ```
  Expected: all `2026_08_25_000001`–`000006` rows present as "Ran".
- [ ] **Dry-run the backfill** and eyeball the summary:
  ```bash
  php artisan pricing:backfill --dry-run
  ```
  Expected: `selling_price`, `mrp`, `active website batch`, `batch_variant_prices`, `batch_wholesale_prices`, `batch_warranty_tiers` all > 0 (some rows may be 0 if no variants/tiers — that's OK).
- [ ] Spot-check active batches in SQL:
  ```sql
  SELECT product_id, COUNT(*) active_batches FROM stock_batches
  WHERE is_active_for_website = 1 GROUP BY product_id HAVING COUNT(*) > 1;
  ```
  Expected: **0 rows** (one active batch per product — enforced in `PricingService`).

---

## 2. Enable the engine (cutover)

- [ ] Backfill for real (writes batch prices from legacy columns):
  ```bash
  php artisan pricing:backfill
  ```
- [ ] Populate the cached website price/stock columns + keep `new_price` in sync:
  ```bash
  php artisan stock:sync-from-batches
  ```
- [ ] Flip the flag in `.env`:
  ```dotenv
  BATCH_WISE_PRICING=true
  ```
- [ ] Clear caches:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  ```

---

## 3. Post-flip verification (do these immediately)

### Admin
- [ ] **Purchases** (`/admin/purchases/manage`): pick a product → the right **4-tab pricing panel** loads (Batch open by default → Variant → Wholesale → Warranty). Change a **Sell Price** → Save → panel reloads; `products.website_price`/`new_price` update.
- [ ] **Set Active** a batch → banner shows the green active badge; only one active per product.
- [ ] **Product edit** (`/admin/products/{id}/edit`): variant price fields are **read-only**, wholesale card shows a "managed on Purchase page" note, and the **Batch Pricing Summary** card shows the active batch price/stock.
- [ ] **Purchase edit** (`/admin/purchases/{id}/edit`): the "Batch Pricing (this purchase)" card shows editable Sell/MRP.
- [ ] **POS**: add a product → batch dropdown lists sellable batches with **sell price**; change the batch → the line price updates.

### Storefront (incognito window)
- [ ] A product with an active batch shows the **active batch price** on cards + detail; variant selector shows batch-variant prices.
- [ ] A product with **no active batch** shows **Out of Stock** (badge / disabled add-to-cart) even if POS stock exists.
- [ ] Add to cart → checkout → place a COD order: `order_details.sale_price` = active batch price; `stock_batch_id` set; batches deducted FIFO.
- [ ] **FIFO allocation spot-check** (optional, via tinker after an order):
  ```php
  App\Models\OrderDetails::latest()->first()->batch_ids
  ```
  Should show an array of `batch_id`/`qty` portions.

### Data sanity
- [ ] `products.stock` still equals `SUM(stock_batches.remaining_qty)` (POS truth) — run `stock:sync-from-batches` again and it reports **"No mismatches"**.
- [ ] `products.website_stock` = website-enabled batch total; `products.website_price` = active batch sell price for sampled products.

---

## 4. Rollback (if anything is wrong)

The engine is fully reversible because all `batch_*` tables + `website_*` columns are **additive** and the legacy path is untouched when the flag is off.

- [ ] **Step 1 — flip the flag off (2 seconds, zero data loss):**
  ```dotenv
  BATCH_WISE_PRICING=false
  php artisan config:clear
  ```
- [ ] **Step 2 — verify legacy behavior:** storefront prices revert to `products.new_price` (the accessors stop overriding), POS/cart use legacy resolution, product page pricing editors return.
- [ ] **Step 3 (only if you want to fully remove):** roll back the migrations (data in `batch_*` tables is lost; `products.stock`/`new_price` are kept):
  ```bash
  php artisan migrate:rollback --step=6
  ```
  > ⚠️ Run this only after confirming legacy prices are acceptable. The `stock:sync-from-batches` values already written to `products.stock` are correct (sum of batches), so no restore is needed for stock.

**Known behaviors to be aware of (not bugs):**
- With the flag ON, `new_price` reads **app-wide** (admin, reports) return the **active batch price** (the Product accessor). This is intended; admin screens should be reviewed once.
- `products.new_price` is kept in sync with the active batch price (so catalog sort/filter follow the batch). Reverting the flag stops the sync; stored `new_price` values remain whatever they were last synced to.
- Products with **no batches** show Out of Stock on the website when the flag is ON (strict rule).

---

## 5. Post-cutover follow-up (next 2–3 days)
- [ ] Monitor `php artisan tinker` + error logs for `PricingService` / batch exceptions:
  ```bash
  tail -f storage/logs/laravel.log
  ```
- [ ] Watch the `stock:sync-from-batches` command output via cron for drift.
- [ ] Add an active batch whenever a new purchase is made (the purchase form's **"Set as website active batch"** checkbox does this) so new stock is sellable on the website.
- [ ] Update staff docs: prices are now managed on **Purchases**, not the Product page.

---

*If any step errors, stop, screenshot, and roll back via Section 4. Do not leave the flag half-on.*
