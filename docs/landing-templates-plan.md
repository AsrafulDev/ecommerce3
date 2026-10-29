# Landing page templates — plan and development todos

Status: **Phases 1, 4, 5, 6 and 7 built; Phases 2 and 3 partly built.** Still open: warranty on the checkout (D14), variant stock on order, a browser test of the editor, the two other templates (waiting for designs), and the conversion of old campaigns (Phase 8).

Owner: Ecommerce Pro (Laravel `lara` app). Related: `docs/architecture.md`.

## Progress

**Built**
- **Old system removed:** the old campaign create and edit pages, the old campaign output page, and the actions and routes only they used. The campaign list stays, with switch on/off and delete. The old `campaigns` tables and rows are kept (D12). No one uses the old landing system, so its conversion is not planned.
- **Storefront:** the `luxury` template (provisional name for the first template) at `/landing/{slug}`. It has shared section partials, a checkout with variant choice, and a presenter that reads the JSON content. A template can override any section with its own file under `landing/{template}/sections/`; otherwise the shared section is used.
- **Checkout labels and placeholders** are template content, so the editor can change them. Placeholders are edited as a line under each field.
- **Pricing (Phase 4):** validity window, extra discount, the landing's own coupon, and the rule that the larger of the two applies. The shop refuses a landing's coupon code. Landing orders use the shop's existing discount path.
- **Admin (Phases 5–6):** the Landing Pages list with a create popup (title, template, product). The editor shows the luxury page in an editable frame. Text is edited in place, images come from the media gallery, sections switch on and off, colours change, list items are added and removed. Settings cover publishing, validity, extra discount and coupon. Saves go through validated JSON endpoints.
- **Permissions (Phase 7):** `landing-list`, `landing-create`, `landing-edit`, `landing-admin`. Staff manage their own pages; `landing-admin` sees all. The Admin role passes every check.
- **Bug fixed on the way:** the checkout used the coupon model without importing it, so every order with a coupon failed. Fixed.

- **Fashion and Gadget templates** (from the ELAN and VOLTA designs): each has its own section list, its own page and style, and reuses the shared sections for cards, articles, galleries, tables, reviews, FAQ and the closing call to action. Both appear in the create popup and the editor, and their content is editable like the luxury template.
- **Extra discount and coupon** are hidden and disabled in the editor for now. Saving the settings no longer clears a stored discount when the discount fields are not sent. Their future design is planned, not built.

**Still open**
- Warranty choice on the luxury checkout (D14).
- Variant stock: the stock engine takes stock from batches and does not tie a variant order to that variant, so variant stock is not reduced on order. This was already true for normal variant sales. Fixing it means changing the stock engine.
- Concurrency lock on variant stock (Phase 3 acceptance).
- The editor's JavaScript has not been run in a browser. The server side is tested.
- Template switch is deliberately not offered (D7).
- The other two templates (waiting for designs, D1).
- Conversion of old campaigns (Phase 8). Not needed while no one uses the old system.

---

## 1. Goal

1. Offer **three landing page templates** now, with more to come later. Every template has the same checkout (order form) behaviour.
2. The **checkout section** supports **product variants** (colour, size, etc.), each with its own price and stock.
3. **Admin** (and clients) manage landing pages from one place:
   - a list of landing pages,
   - a **Create** popup that asks for a landing title and a template, then opens the template editor,
   - a **template editor** that looks exactly like the live page, but where text and images can be edited in place.
4. All landing content is stored in the database as **JSON**, keyed by **element id**, so different templates can have different layouts and fields.
5. A landing page can have a **validity window**, an **auto-generated coupon** and an **extra discount**.
6. Sections can be switched off, but the **checkout section is always on**, and **at least one content section must stay on** beside it.

---

## 2. Current state (what the code does today)

| Area | Found | Gap |
|---|---|---|
| Live landing page | `resources/views/frontEnd/layouts/pages/campaign/campaign.blade.php` (about 1050 lines). Sections are switched by `Campaign::sectionVisible()`; text comes from `Campaign::label()`. | One fixed layout. No template choice. |
| Checkout | The order form is **inline** in `campaign.blade.php` (about lines 670–725). It posts to `customer.ordersave`. | The file the IDE showed, `frontEnd/layouts/partials/landing_checkout.blade.php`, is **empty (0 bytes)**. The checkout is not yet a partial. |
| Product choice | One primary product (`campaigns.product_id`) plus a `campaign_product` pivot for extra products. A radio picker chooses the product. | No variant selector. Variants (`product_variant_prices`) are ignored on the landing page. |
| Storage | `campaigns` table with flat columns plus JSON-ish columns (`sections`, `labels`, `features`, `problem`, `solution`, `benefits`, `trust`, `faq`, `cta`, `reviews`). Created and edited through `backEnd/campaign/create` and `edit`. | Not keyed by element id, and not per template. Adding a new layout means a new set of columns. |
| Admin | `backEnd/campaign/index`, `create`, `edit`, a shared `_form`. Template folder `backEnd/campaign/template/` exists but is **empty**. | The editor is a long form, not an in-place editor. No template choice, no status by date. |
| Layout folder | `frontEnd/layouts/landing/` exists but is **empty**. | No template files yet. |
| Coupons | `coupons` table exists: `code, type, value, min_purchase, valid_from, valid_to, max_uses, used_count, status`. | Not linked to a landing page. |
| Variants | `product_variant_prices`: `product_id, color_id, size_id, price, stock, sku, barcode, image`. | Checkout does not choose a variant. |

---

## 3. Decisions needed (with recommended defaults)

Please answer each one. Where you don't answer, the default is used.

| # | Question | Recommended default |
|---|---|---|
| D1 | What are the **three template designs**? Figma or screenshots for each, and the names you want. | **Agreed:** names TBD. Designs needed before Phase 2. |
| D2 | **One product per landing page** (your wording), or several? | One product. The `campaign_product` pivot stays only for legacy pages. |
| D3 | **Variant pricing:** does each variant use its own price from `product_variant_prices`? Can the landing set a different price per variant? | Each variant uses its own price. The landing does not override prices. If the product has no variants, the product price is used. |
| D4 | **Auto coupon:** what is it? A unique code made per landing page, or a code per order? Who can use it? | A unique code made when the landing is created, stored in the existing `coupons` table, valid only for that landing's product. |
| D5 | **Extra discount:** a percent or a fixed amount? Does it stack with a coupon? | A percent or fixed amount set per landing. It does not stack with a coupon. The better of the two applies. |
| D6 | **Validity date:** start and end? What does a visitor see after the end date? | Start and end date/time. After the end date the page shows a "closed" message and checkout is hidden. |
| D7 | **Who is the "client"?** | **Agreed:** a client is any user who has landing permissions (system admins included). Such a user creates and edits **their own** landing pages, changes colours and text, and cannot switch templates. A user with `landing-admin` sees and edits all landing pages and can switch templates. |
| D8 | **Editing style:** text edited in place on a live-looking page (click text to type), images chosen only from the Media Gallery? | Yes. Rich text is limited to basic formatting. |
| D9 | **Colours:** which colours can the client change? Free colour picker, or a fixed set? | Four colours per landing: primary, accent, background, text. No free CSS. |
| D10 | **Section order:** can the client reorder sections? | No. Order is fixed by the template. Sections can be switched on or off. |
| D11 | **Minimum rule:** "minimum 1 section and checkout" — I read this as: checkout is always on, and at least one content section (hero, problem, etc.) must stay on. Correct? | Yes. |
| D12 | **Existing landing pages** (current campaigns): keep the old page, or convert them to templates? | Keep the old `/campaign/{slug}` page working. Convert later with a command that has a dry run. Nothing is deleted. |
| D13 | **Payment:** same as today (cash on delivery through `customer.ordersave`)? | Yes. |
| D14 | **Warranty:** keep the warranty choice on the checkout? | Yes, as today. |

---

## 4. Target design

### 4.1 Data model

New table `landing_templates` (migration with `Schema::hasTable` / `hasColumn` guards, per repo convention):

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar(191) | Landing title (from the create popup). |
| `slug` | varchar(191) unique | Public URL `/landing/{slug}`. Built from `name`, made unique. |
| `template_key` | varchar(40) | `classic`, `modern`, `bold` (names TBD, see D1). Cannot change after create (D7). |
| `created_by` | unsigned int, FK `users.id` | Owner. Clients see only their own rows. |
| `product_id` | unsigned int, FK `products.id` | One product (D2). |
| `content` | JSON | All text, images, section switches, colours, checkout labels. Keyed by element id (see 4.2). |
| `status` | tinyint | `1` active, `0` draft or off. |
| `valid_from` | datetime null | D6. |
| `valid_until` | datetime null | D6. |
| `coupon_id` | unsigned int null, FK `coupons.id` | Auto coupon (D4). |
| `extra_discount_type` | enum `percent`, `fixed` null | D5. |
| `extra_discount_value` | decimal(10,2) null | D5. |
| `created_at`, `updated_at` | timestamps | |

Indexes: `slug` (unique), `created_by`, `product_id`, `status`.

The old `campaigns` table is untouched (D12).

### 4.2 JSON content and element ids

Every editable value has a **stable element id** of the form `section.element`. The id belongs to the template, so a new template can add its own ids without a schema change.

```json
{
  "theme": { "primary": "#4f46e5", "accent": "#22c55e", "background": "#ffffff", "text": "#0f172a" },
  "sections": {
    "hero":     { "enabled": true },
    "problem":  { "enabled": true },
    "solution": { "enabled": true },
    "features": { "enabled": false },
    "reviews":  { "enabled": true },
    "faq":      { "enabled": true },
    "checkout": { "enabled": true, "locked": true }
  },
  "elements": {
    "hero.eyebrow":        { "value": "Limited time offer" },
    "hero.title":          { "value": "Faster internet" },
    "hero.image":          { "value": "uploads/media/hero.webp" },
    "problem.item.1.title": { "value": "Slow Wi-Fi" },
    "checkout.heading":    { "value": "Order now" },
    "checkout.submit":     { "value": "Confirm order" }
  }
}
```

Rules:

- **Element ids are whitelisted per template.** A save with an unknown id is refused.
- **Every element is stored as `{"value": ...}`.** Its type comes from the template registry, not from the stored JSON: `text` (plain, all tags stripped), `rich` (basic tags only, attributes dropped), `image` (media path under `uploads/` from the Media Gallery only, D8). Colours live under `theme`.
- **Lists** (problem items, FAQ, reviews) use numbered ids: `faq.item.1.q`, `faq.item.1.a`. Adding or removing an item adds or removes ids in the template's list schema.
- `checkout` is `locked: true`. The server refuses to set `checkout.enabled` to `false`.
- **Minimum rule (D11):** a save is refused unless `checkout` is enabled and at least one other section is enabled.

### 4.3 Template registry

A PHP class `App\Services\Landing\TemplateRegistry` holds, for each template key:

- its **section list** and order,
- its **element schema** (every allowed id, its type, its default value and its maximum length),
- its **default content** (the JSON a new landing starts with),
- the **Blade skeleton** used on the storefront and in the editor.

Adding a template means adding one registry entry, one skeleton and its CSS. No database change.

### 4.4 Shared section partials

Each section is a partial that reads its values through one helper, `$el('hero.title')`. The three templates differ only in their skeleton and CSS, so the same content renders in each.

- Storefront: `resources/views/frontEnd/layouts/landing/{template_key}/page.blade.php` plus `resources/views/frontEnd/layouts/landing/sections/*.blade.php`.
- Checkout: `resources/views/frontEnd/layouts/partials/landing_checkout.blade.php` (currently empty, will hold the checkout section).

### 4.5 Checkout with variants (D3, D14)

- Shows the product's variants as choices (colour, size) with each variant's price and stock.
- Price and stock are checked **on the server** when the order is placed. The browser's price is never trusted.
- Stock is reserved with a row lock (`lockForUpdate`) on the variant row, so two buyers cannot take the last item.
- Posts to `customer.ordersave` with the chosen `variant_price_id`, the coupon or discount, and the warranty choice.
- If the product has no variants, the form works as today.

### 4.6 Pricing, coupon and validity (D4, D5, D6)

One service, `App\Services\Landing\LandingPricing`, decides the price shown and charged:

1. Landing must be active and inside `valid_from` / `valid_until`. Otherwise checkout is hidden.
2. Base price = the chosen variant's price (or product price).
3. Extra discount (if set) and the auto coupon (if set and valid) are compared. The larger reduction applies.
4. Coupon use is counted in `coupons.used_count` inside the order transaction.

The same service is used for the price shown on the page and the price charged on order. This is the only place discount logic lives.

### 4.7 Admin

- **List** (`backEnd/campaign/index` becomes the landing list): title, template, product, status, validity, orders count, owner. Filter by status and owner.
- **Create popup:** landing **title** and **template** (radio cards with a small preview of each). Submit creates a draft with the template's default content and redirects to the editor.
- **Editor** (`backEnd/campaign/template/{id}`):
  - Same markup as the live page, with `data-el` attributes on each editable element.
  - Click a text element to edit it in place. Images open the Media Gallery only (D8).
  - Section switches, with the minimum rule enforced in the browser and on the server.
  - Colour panel: four colours (D9).
  - Saves in the background (autosave on change, with a "Saved" indicator). Each save sends only the changed element ids.
  - **Live view** toggle: the same page, read-only.
  - No template switch control for clients (D7). Admin can change the template key only through a support action, not from the editor.

### 4.8 Permissions and ownership (D7)

- Spatie permissions: `landing-list`, `landing-create`, `landing-edit`, `landing-colour`, `landing-admin`.
- A **client** is any user who has `landing-create` and `landing-edit` (system admins included). A client manages only the landing pages it created (`created_by === auth()->id()`).
- A user with `landing-admin` sees and edits every landing page and can switch a page's template.
- Switching `template_key` needs `landing-admin` and is not on the editor screen.

### 4.9 Storefront URLs

- New: `/landing/{slug}` (renders the template for the row).
- Old: `/campaign/{slug}` keeps working until conversion (D12).

---

## 5. Development todos

Each phase lists its files and its acceptance test. A phase is done when its acceptance test passes and the full suite still passes.

### Phase 0 — Decisions and assets
- [ ] Owner answers D1–D14 (or accepts defaults).
- [ ] Owner provides the three template designs (Figma link or screenshots), with mobile and desktop views.
- [ ] Owner confirms the template names and which one is the default.

### Phase 1 — Data model and content rules (done)
- [x] Migration `create_landing_templates_table` (guards on table and columns).
- [x] Model `App\Models\LandingTemplate` with JSON cast on `content`, `status` boolean, date casts; `uniqueSlug()`.
- [x] `App\Services\Landing\TemplateRegistry`: element schema and default content. Only the provisional `classic` template exists; the other two wait for the designs (D1).
- [x] `App\Services\Landing\ContentValidator`: whitelist of ids, types and lengths; minimum-section rule; locked checkout. Errors are raised as `InvalidLandingContent`.
- [x] Slug generation (`LandingTemplate::uniqueSlug`).
- **Acceptance:** unit tests for the validator (unknown id refused, checkout cannot be disabled, all sections off refused, length limits, rich text sanitised). Met: 16 validator tests and 5 model/table tests pass.

### Phase 2 — Template skeletons and shared sections (storefront) (partly done: luxury only)
- [ ] Shared section partials in `frontEnd/layouts/landing/sections/` reading `$el()`.
- [ ] Skeleton for template 1 in `frontEnd/layouts/landing/{key}/page.blade.php`, then templates 2 and 3 once designs arrive.
- [ ] Route `GET /landing/{slug}` and controller method `LandingController@show`.
- [ ] Mobile layout check (16px side gutter, no horizontal scroll) for each template.
- **Acceptance:** each template renders with its default content and with every section switched off except checkout and one other.

### Phase 3 — Checkout partial with variants (partly done: variant choice, price, refusals; open: variant stock, lock, warranty)
- [ ] Fill `frontEnd/layouts/partials/landing_checkout.blade.php` (currently empty): heading, product, variant choices, price, warranty choice, area, submit.
- [ ] Variant choice reads `product_variant_prices` for the product; out-of-stock variants shown disabled.
- [ ] Server side in the order save: variant belongs to the product, stock available (locked row), price taken from the variant.
- [ ] Products with no variants behave as before.
- **Acceptance:** an order for a variant decreases that variant's stock only; two concurrent orders for the last unit cannot both succeed (test with two requests).

### Phase 4 — Pricing, auto coupon, extra discount, validity (done)
- [x] `App\Services\Landing\LandingPricing`: validity, extra discount, coupon checks, and the larger-of rule. The landing order sends the discount through the shop's existing session path (`coupon_code` / `discount`), so the checkout records it.
- [x] Auto coupon: `LandingPricing::issueCoupon()` creates a unique `LP…` code in `coupons`, limited to the landing window, and links it by `coupon_id`. The admin create form (Phase 5) calls it, with the type and value chosen there.
- [x] Extra discount fields and rule (D5).
- [x] Validity check (D6): the storefront shows the closed notice, and the order save refuses a closed or mismatched landing.
- [x] The shop refuses a landing's coupon code (D4: the coupon works only on its landing page).
- Note: `coupons` has no product column, so product scoping is enforced by the landing and by the shop's refusal above, not by the coupon row.
- **Acceptance:** tests for: discount applied once, larger of extra and coupon wins, expired landing refuses order, coupon use count increments once per order.

### Phase 5 — Admin list and create popup (done)
- [x] Landing list (`backEnd/campaign/index` rebuilt for `landing_templates`) with filters.
- [x] Create popup: title, template radio cards, submit creates the row with default content and redirects to the editor.
- [x] Routes under `admin/landing/...` with permission middleware.
- **Acceptance:** creating a landing with a blank title is refused; a client sees only its own rows.

### Phase 6 — Template editor (done)
- [x] Editor page `backEnd/campaign/template/edit.blade.php`, reusing the skeleton with `data-el` attributes.
- [x] Inline text editing (contenteditable, basic formatting only) with autosave.
- [x] Image picker wired to the Media Gallery only.
- [x] Section switches with minimum rule (browser and server).
- [x] Colour panel (four colours) with live update.
- [x] Live view toggle (read-only).
- [x] Endpoint `PATCH admin/landing/{id}/content` accepts changed elements only; returns the saved content.
- **Acceptance:** an edit saved in the editor shows on the live view after reload; a client cannot save a disabled-checkout or all-sections-off state; text edits are sanitised.

### Phase 7 — Permissions and client role (done)
- [x] Permissions seeded (`landing-*`). No separate client role: clients are users with landing permissions (D7).
- [x] Ownership check on every action.
- [x] Template switch limited to `landing-admin`.
- **Acceptance:** a client cannot open, edit or view another client's landing (403); a client cannot change `template_key` even by a crafted request.

### Phase 8 — Conversion of existing campaigns (D12) (not planned: nobody uses the old system)
- [ ] Command `landing:convert-campaigns {--dry-run}` mapping each campaign row to a `landing_templates` row with template `classic` (or the closest match).
- [ ] Old `/campaign/{slug}` page kept until the owner says to retire it.
- **Acceptance:** dry run prints the plan and writes nothing; a real run leaves the `campaigns` table unchanged.

### Phase 9 — QA and release
- [ ] Browser check of each template on mobile and desktop, English and Bangla text.
- [ ] Concurrency test for stock (Phase 3).
- [ ] Full test suite green; `php artisan test` on a clean checkout.
- [ ] Commit in logical groups (registry, checkout, pricing, admin, editor, permissions, conversion).

---

## 6. Test plan (summary)

| Area | Test |
|---|---|
| Validator | Unknown id refused; checkout cannot be disabled; zero content sections refused; length and type rules; rich text sanitised. |
| Templates | Each template renders with default content; with minimum sections; no element id missing from the skeleton. |
| Checkout | Variant belongs to the product; stock decreases once; last-unit race; no-variant product works. |
| Pricing | Extra discount vs coupon (larger wins); coupon used once per order; expired landing refuses order. |
| Admin | Create requires title and template; list filters; editor saves changed ids only. |
| Security | A client sees only its own rows; a `landing-admin` sees all; a client cannot switch template; cannot disable checkout; text sanitised; image paths only from Media Gallery. |
| Migration | Dry run writes nothing; real run leaves `campaigns` unchanged. |

---

## 7. Risks and how we handle them

| Risk | Handling |
|---|---|
| Rich text opens an XSS hole | Store only basic tags (`b`, `i`, `u`, `br`, `p`, `ul`, `li`); strip everything else on save and again on output. |
| Two buyers take the last variant | Row lock on the variant during order save; test with concurrent requests. |
| Discount abuse (repeat coupon use) | Coupon `max_uses` checked inside the order transaction; `used_count` incremented in the same transaction. |
| JSON drifts from the template | Validator uses the registry, so unknown ids are refused; a nightly check is optional. |
| Editor autosave overwrites another editor's change | Save sends changed ids only, with the content version; a stale save is refused and reloaded. (Add a `version` column if needed.) |
| Bangla text and fonts | Test each template with Bangla copy; check line wrapping in buttons. |
| Mobile layout | 16px side gutter; test at 360px wide. |

---

## 8. Open items for the owner

1. The three template designs: Figma links or screenshots, and their names (D1). Needed before Phase 2.
2. Nothing else is open. All other decisions are agreed as the defaults in section 3.

---

## 9. Files (planned)

| Path | Purpose |
|---|---|
| `database/migrations/*_create_landing_templates_table.php` | Table (4.1). |
| `app/Models/LandingTemplate.php` | Model, casts. |
| `app/Services/Landing/TemplateRegistry.php` | Templates, element schema, defaults (4.3). |
| `app/Services/Landing/ContentValidator.php` | Save rules (4.2). |
| `app/Services/Landing/LandingPricing.php` | Price, coupon, discount, validity (4.6). |
| `app/Http/Controllers/Frontend/LandingController.php` | Storefront page. |
| `app/Http/Controllers/Admin/LandingController.php` | Admin list, create, editor, content save. |
| `resources/views/frontEnd/layouts/landing/{key}/page.blade.php` | Template skeletons. |
| `resources/views/frontEnd/layouts/landing/sections/*.blade.php` | Shared sections. |
| `resources/views/frontEnd/layouts/partials/landing_checkout.blade.php` | Checkout partial (currently empty). |
| `resources/views/backEnd/campaign/index.blade.php` | Landing list (rebuilt). |
| `resources/views/backEnd/campaign/template/edit.blade.php` | Template editor. |
| `tests/Feature/Landing/*Test.php`, `tests/Unit/Landing/*Test.php` | Tests (section 6). |
