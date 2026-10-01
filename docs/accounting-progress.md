# Accounting Progress — Source of Truth

Legend: `[x]` COMPLETE · `[~]` IN PROGRESS · `[ ]` NOT STARTED · `[!]` BLOCKED · `[?]` NEEDS REVIEW

"COMPLETE" means implemented + tested + verified + regression passed. Files existing ≠ complete.

---

# Current Development Status

### 2026-10-01 Lite Accounting pre-implementation audit

- Lite menu: `Transactions` contains the dashboard, fund/cash book, expenses,
  and operational reports. Advanced `Accounts` is separately gated.
- Routes/pages: `/accounts`, `/fund`, `/expenses`, and existing reports are
  reused. Lite customer/supplier ledger pages do not yet exist.
- `fund_transactions` stores direction, legacy source/source_id, amount, notes,
  actors, and write-only balance snapshots; it has no fund_id and remains a
  virtual single till.
- Observed sources are sale, refund, order_refund, refund_reversal, expense,
  supplier_payment, employee_salary, employee_bonus, withdraw, manual_add,
  warranty, and warranty_resell. Manual money-in nature is chosen explicitly
  at the controller/accounting boundary and is not stored on legacy rows.
- Creation paths are FundHelper, PaymentCollectionService, and the Fund,
  Expense, Purchase, Refund, Salary, Bonus, and Warranty controllers. Reads
  are in FundHelper, AccountsController, FundController, reports, accounting
  sync/manual-entry integration, and views.
- COGS/profit already uses the shared `CogsCalculator`; no second formula is
  introduced.
- Safe change: additive nullable `transaction_category`; deterministic source
  mappings only. Ambiguous historical manual money-in rows remain null.
- Baseline: `php artisan test` — 293 passed, 1,144 assertions, 0 failures,
  0 skipped.

_Reviewed: 2026-09-29 — full two-repo audit (no code changes made during this audit)._

## Repository Status

### Lara

### Lite Accounting Phase 2 (2026-10-01)

- Customer Ledger: implemented under Transactions → Customer Accounts. It is
  derived from `orders` and `order_payments`; charge rows are order totals and
  payment rows are the order-payment ledger. Closing balance is checked against
  the sum of operational `orders.due_amount`.
- Supplier Ledger: implemented under Transactions → Supplier Accounts. It is
  derived from `purchases` and `supplier_payments`; closing balance is checked
  against the sum of operational `purchases.due_amount`.
- Supplier payment trace: new purchase payments already persist a
  `SupplierPayment`, link it through `fund_transaction_id`, and set the fund
  row's `source_id` to that payment ID. No fake IDs or historical rewrite was
  added.
- Category coverage: deterministic source categories are assigned by the
  `FundTransaction` model; manual money-in is assigned from explicit nature.
  Historical ambiguous `manual_add` rows remain nullable.
- Lite P&L remains unchanged and continues to use `CogsCalculator`; customer
  payments, supplier payments, capital, and withdrawals are cash movements,
  not profit events. Returns/refunds remain governed by existing operational
  behavior and were not reclassified speculatively.
- Phase 2 targeted verification: 22 passed, 113 assertions. Full regression
  from the preceding phase remains 293 passed, 1,144 assertions.

### Lite Ledger hardening (2026-10-01)

- Added dedicated regression coverage for customer full/partial/multiple and
  decimal payments, supplier purchase/payment reconciliation, supplier fund
  traceability, and payment category semantics.
- Return audit: customer returns/refunds and supplier returns are separate
  operational flows. Existing return/restock records do not provide one
  universal ledger event that can safely represent charge reduction versus
  cash refund, so no speculative ledger rows were added.
- Opening-balance audit: customer has no authoritative opening-balance field;
  supplier has `opening_balance`, but it is a resulting/master-data value in
  the current flow rather than a persisted dated opening transaction. It is
  therefore not fabricated into ledger history.
- New dedicated tests: 3 passed, 13 assertions. Historical nullable categories
  remain allowed; only supported new writes are required to classify.

- Branch: `premium` (ahead of `origin/premium` by 1 commit — not yet pushed)
- Latest commit: `2bca487` "Add Lite/Advance accounting: availability seam, cash rule unification, gateway cash-in"
- Working tree: clean except `.phpunit.cache/test-results` (cache noise) and untracked `.commandcode/`, `.qoder/` (tool dirs — not project work)
- Uncommitted work: none meaningful
- Package link: `composer.json` requires `softmit/bd-double-entry: @dev` via path repo `../bd-double-entry` (symlinked into `vendor/softmit/bd-double-entry`). All 6 `accounting_*` migrations are **Ran** per `migrate:status`.

### bd-double-entry

- Branch: `master` — **BASELINE COMMITTED 2026-09-29: `884685e` "Initial double-entry accounting engine baseline"** (43 files, 3791 insertions; `.gitignore` added; no secrets/vendor/env present at commit time)
- Verified before commit: host accounting suites green against this exact state — `tests/Feature/Accounting` + `tests/Unit/Accounting` + Fund tests = **134 passed, 0 failed**
- Latest commit: `884685e`
- Working tree: whole package untracked (`composer.json`, `config/`, `database/migrations/` ×6, `docs/integration-laravel.md`, `src/` ×28 PHP files)
- Uncommitted work: the entire Phase-1 engine
- No `tests/`, no `phpunit.xml`, no README inside the package — its test suite lives in lara (`tests/Feature/Accounting/**`, `tests/Unit/Accounting/MoneyTest.php`)

---

## Ecommerce Financial Features (Lite Accounting — owned by lara)

| Feature | Status | Current Implementation | Problem | Next Action |
| --- | --- | --- | --- | --- |
| Fund | [x] | Virtual single till over `fund_transactions` (no Fund model in Lite); Accounts dashboard shows balance/trend/sources | `balance_before/after` write-only, never read; no `category`/`nature` column on rows | Leave as-is until reconciliation needs per-fund; do not resurrect dead columns |
| Fund Transaction | [x] | `FundHelper` credit/debit (idempotent, capped realizable rule), `PaymentCollectionService` single collect-money primitive; sources: sale, manual_add, warranty(_resell), refund_reversal / expense, refund, order_refund, supplier_payment, employee_salary/bonus, withdraw | Free-form varchar `source`, no PHP enum/constants for most values | Introduce source constants + classification without breaking existing rows |
| Income | [x] | Lite: realizable-cash rule `FundHelper::income()` (excludes uncollected COD via `uncollectedSaleCredits()`); Full: `ManualEntryService::moneyIn()` with mandatory `nature` (owner_capital \| other_income) | Lite counts capital as cash-in (correct for cash statement, but dashboard must never call it "income" — verify wording) | Rename dashboard labels to "Cash In" if they say "Income" |
| Expense | [x] | `Expense` model + `expense_logs`; ExpenseController writes expenses-table row + fund OUT row; Full side posts Dr Expense / Cr Cash | `ExpenseController` directly imports `Softmit\DoubleEntry\Enums\SourceType` → fatals if package removed (seam violation) | Wrap package touchpoints behind the availability check |
| Customer Due | [x] | `Order::due_amount` derived by `recalculatePaymentTotals()` from `order_payments` ledger | No customer-level receivables aggregate (computed on demand only) | Acceptable for Lite; add aggregate view via ledger screen |
| Supplier Due | [~] | `Supplier::current_due` stored column, maintained by PurchaseController; `supplier_payments` table exists | Fund OUT rows for `supplier_payment` have `source_id = null` → untraceable payments | Backfill/fix linkage to `supplier_payments.id` |
| Customer Ledger | [ ] | **No Lite ledger screen exists** (customer views: index/edit/profile/search only). Party ledger exists only in Full (`AccountingLedgerController::party`) | Gap acknowledged in docs/architecture.md #3 | Build Lite customer ledger (Date/Type/Ref/Debit/Credit/Balance from orders + payments) |
| Supplier Ledger | [ ] | Same as Customer Ledger — not started | Gap | Build Lite supplier ledger from purchases + supplier_payments |
| Purchase | [x] | PurchaseController store in transaction, batches via StockManagementService, `purchases`/`purchase_items` | No auto journal (gateway `purchaseReceived` never called — Null bound anyway) | Wire via events later (Step 10 order) |
| Sale | [x] | Orders + `order_details.cogs`; fund credit on delivery/COD | `creditSale` called directly from ~8 OrderController sites + RedXWebhook, bypassing PaymentCollectionService | Route through single primitive |
| Payment | [x] | `order_payments` ledger + `payments` synced by `PaymentCollectionService`; all 5 gateway controllers hook `paymentReceived` | Only gateway method ever called; on Null impl (no-op) | Real gateway impl in Step 9/10 |
| Refund | [~] | `refunds` table, FundHelper debitRefund + `refund_reversal`; idempotent guards | Three separate FundHelper guards interplay; refund/out dates vs sale created_at can split across months | Unify reversal path through PaymentCollectionService |
| Return | [~] | Purchase/supplier returns go through StockManagementService (tests pass) | No financial ledger screen for return value yet. Known gaps preserved as-is (documented, not silently changed): the admin `markReturned` action bypasses `handleStatusChange`, so it neither NULLs `order_details.cogs` nor restocks; web storefront checkout consumes stock but discards the computed cogs result, so those lines fall back to the snapshot | Verify customer return ↔ refund linkage during Step 5 |
| COGS | [x] | **ONE shared rule — `App\Services\CogsCalculator`** (Commerce/Lite-owned, package-ignorant): stored realized `order_details.cogs` (a LINE TOTAL) is authoritative, else the line's own `purchase_price × qty` snapshot; the live `Product.purchase_price` is never consulted (history cannot be rewritten by today's price). Recognition: status ∈ {delivered, completed} + **created_at** window — sales and COGS always come from the same recognized order set. Consumed by AccountsController dashboard, ReportController profitLoss, DashboardController today-profit | CURRENT LIMITATION: orders have no delivery timestamp (no delivered_at/completed_at/paid_at exists); created_at is used as the stable immutable basis; updated_at is forbidden (any unrelated edit would move COGS across periods). Long term: stamp a one-time `delivered_at` in OrderStatusService and switch the one method `recognizedOrders()` | Land `delivered_at` migration + backfill when the accounting-phase plan reaches it (reported, not implemented — 2026-09-29) |
| Profit/Loss | [x] | `ReportController::profitLoss` (Basic Profit Summary): recognized orders via `CogsCalculator`, Sales − Refunds − COGS + OtherIncome − Expenses − Salaries/Bonuses; capital explicitly excluded; + CSV export. Refund remains contra-revenue only — it never reverses COGS twice; a RETURNED order leaves the recognized set entirely (engine NULLs its cogs) | Other Income uses warranty gross, not margin | Review margin basis in Step 5 |
| Stock Valuation | [x] | `AccountsController`: `StockBatch.remaining_qty × unit_cost` | Lite-only; Advanced Inventory GL reconciliation pending | Reconcile vs Inventory GL when Advanced ON |

### Advanced Accounting seam (lara side)

| Item | Status | Evidence |
| --- | --- | --- |
| `AccountingAvailability` | [x] LIVE (2026-09-29) | `available()` = package installed (the ONLY class_exists probe in app code), `enabled()` = available + `double-entry.enabled`; consulted by container binding, `advanced-accounting` route middleware, sidebar menu |
| `AdvancedAccountingGateway` (official name; was FullAccountingGateway) | [x] | Host-owned interface: available/enabled + manual-money ops the Lite screens genuinely need (recordExpense/recordMoneyIn/recordWithdrawal + lock/nature queries) + paymentReceived. Dead commerce-event methods dropped until those integrations land (P7 order) |
| `NullAdvancedAccountingGateway` | [x] | Bound when disabled/absent; inert, package-class-free. Proven by `AdvancedAccountingDisabledTest` (8 tests): Lite flows work, zero journals, `ManualEntryService`/live gateway never resolved, accounting routes 404 |
| `DoubleEntryAdvancedAccountingGateway` (real adapter) | [x] | Delegates manual ops to ManualEntryService; paymentReceived posts when the customer-payment phase lands |
| `ManualEntryService` | [x] integration layer | Posting rules unchanged (keys, cutover refusal, failure reporting); `ManualPostingResult` now carries `journalNo` string instead of a package model |
| `config/double-entry.php` | [x] package-free | AccountRole constants replaced by plain string values — boot no longer fatals when package is absent |
| Commerce controllers | [x] clean | Expense/Fund depend only on the gateway interface. Softmit imports remain ONLY in: Accounting/* (6, middleware-gated), ManualEntryService, OpeningBalanceService, DoubleEntry gateway, AccountingAvailability probe |
| Accounting UI screens | [x] | routes `admin.accounting.*` + menu now gated by gateway `enabled()` underneath the `accounting-*` permissions |
| Events/listeners posting journals | [ ] NOT STARTED | No `app/Events`/`app/Listeners` dirs; 100% synchronous controller calls — P7 introduces the flow |
| Lite money-route permissions | [~] PARTIAL | Fund/expense routes still auth:admin+admin only (no per-action `permission:` middleware) |

### Purchase UX (ecommerce requirement — recorded, not implemented)

| Item | Status | Notes |
| --- | --- | --- |
| Quick-create Supplier/Product/Category/Brand from Purchase page | [ ] NOT STARTED | `resources/views/backEnd/purchases/index.blade.php` (doubles as create form) has no modals/quick-create affordances. Must stay in lara — never in bd-double-entry |

---

## Advanced Accounting Package (softmit/bd-double-entry)

| Feature | Status | Existing Code | Missing Work |
| --- | --- | --- | --- |
| Account Types | [x] | `Enums/AccountType.php` — 6 types, normalBalance map, P&L types | — |
| Accounts | [x] | `Models/Account.php`, derived balances (no stored balance), `role` unique, code unique | hierarchy (`parent_id`) wired but consumed by nothing (needed for Balance Sheet rollup) |
| Chart of Accounts | [x] | `ChartOfAccountsSeeder` (30 role accounts + ~28 manual, conflict rollback), `AccountRegistry` (role-key/id resolution), `AccountRole` (30 semantic constants incl. cash/bank/AR/AP/inventory/cogs/sales_revenue/owner_capital/owner_drawings) | none semantic-wise; never hardcode IDs — role mapping already correct |
| Journal Entry | [x] | `Models/JournalEntry.php` + migration: journal_no unique, posting_key unique, 3-trace fields, reversal_of_id, immutability guards | — |
| Journal Lines | [x] | `Models/JournalLine.php`, per-line party trace, indexes | gap: inserts of NEW lines onto a POSTED journal aren't blocked at model level (update/delete only) |
| Posting Engine | [x] | `JournalPoster`: bcmath-exact balance validation, atomic DB::transaction, idempotency (pre-check + race-loser recovery), per-year locked sequences, cutover-date refusal | gap: `post()` idempotency short-circuit can return an existing **DRAFT** without posting it (JournalPoster.php:103–108) |
| Debit/Credit validation | [x] | `UnbalancedJournalException`, `JournalLineDraft::validityError()` (one-side rule), all-zero rejection | — |
| General Ledger | [x] | `LedgerService::forAccount()` running-balance paginated, shared `LedgerLines` query (POSTED+REVERSED) | — |
| Party Ledger | [x] | `LedgerService::forParty()` AR/AP/employee sub-ledgers, excludes cash leg correctly | — |
| Cash/Bank positions | [x] | `cashPositions()`, `totalCash()`, `FundAccount` fund_key→account_id mapping | — |
| Trial Balance | [x] | `TrialBalanceReport` opening/period/closing, contra-aware columns, independent balanced flags | — |
| Income Statement | [x] | `ProfitAndLossReport` from **posted journal lines only** (Part W satisfied) | — |
| Balance Sheet | [ ] NOT STARTED | nothing (docs admit it) | build from AccountType rollup + hierarchy |
| Opening Balance | [~] | package: `SourceType::OPENING`, cutover exemption, contract doc; host: `OpeningBalanceService` + tests | deliberately host-side; fine |
| Reversal | [x] | `ReversalService`: mirror journal, own idempotency key `reversal:{id}`, transactional both ways, double-reversal reject, immutability two-layer | — |
| Fiscal Period | [ ] NOT STARTED | only cutover_date + per-year numbering exist | table/service/status enforcement + retained-earnings closing |
| Source Trace | [x] | `SourceType` closed enum (17 events), composite index, `sourceLabel()`, `source_routes` config hook | `source_routes` empty skeleton in host config |
| Party Trace | [x] | `PartyType` (9 cases), whitelist resolution via `config('double-entry.parties')` (anti injection) | — |
| Actor Trace | [x] | `JournalDraft::make()` auto-capture via `actor_guards`, `ActorResolver` names/deleted-user handling | — |
| Idempotency | [x] | deterministic keys `{type}:{id}`, DB unique, race-safe retry, `PostingFailureLogger::attempt()` | G-2 draft-return gap above |
| Money | [x] | `Money` bcmath scale-2 end-to-end | nit: `format()` float roundtrip |
| Reconciliation | [ ] NOT STARTED | no Lite↔Advanced reconciliation screen | Step 12: due↔AR, fund↔Cash GL, batches↔Inventory, basic profit↔IS |
| Package tests | [!] | **none in package** — 11 suites live in lara host (`tests/Feature/Accounting/**`) | acceptable short-term; add testbench if package ships standalone |
| Known runtime bug | [!] | `SyncDefaultsCommand.php:21` reads `$result['updated']`; `AccountingDefaults::sync()` returns key `verified` → PHP 8 undefined-key warning on every `accounting:sync-defaults` | one-line fix |
| Date validation gap | [!] | `transactionDate` never normalized: `'01-10-2026'` passes lexicographic cutover compare and yields wrong JV year | validate/normalize to Y-m-d in draft or poster |

---

### Advanced Accounting Integration Phase 1 (2026-10-01)

- Re-audit confirmed the first slice is live: Expense, Other Income, Owner
  Capital, and Owner Withdrawal flow through the gateway and live adapter when
  enabled, and the inert Null gateway when disabled.
- Existing role mappings, deterministic posting keys, source/actor traces,
  balanced journals, idempotency, P&L exclusions, trial balance, GL, reversals,
  and disabled-mode safety are covered by host tests.
- The package defaults sync command was fixed separately to read its returned
  `verified` count instead of the nonexistent `updated` key.
- Customer payment, supplier payment, purchase, sale, COGS, returns, refunds,
  and VAT/Mushak remain intentionally deferred.

### Advanced Accounting Phase 2A — cutover readiness (2026-10-01)

- Added `AdvancedAccountingGateway::readyForLivePosting()` as the single host
  seam for live-posting readiness. Disabled/unavailable Advanced is never ready;
  an opening DRAFT is never ready; readiness requires the configured cutover
  date and one balanced POSTED journal with posting key `opening:balances`.
- Existing `OpeningBalanceService` remains authoritative and already derives
  party-level customer AR and supplier AP lines, plus supported cash, inventory,
  and balancing equity lines. It does not fabricate zero-balance parties.
- Boundary rule: the opening snapshot represents activity before the configured
  cutover date; live event eligibility begins at `00:00:00` on the cutover date.
  The package's date guard enforces this at journal posting.
- No customer/supplier payment journals were added. Future payment integration
  must call the readiness seam first, preventing settlement against unmigrated
  AR/AP.
- Added readiness tests: 3 passed, 7 assertions. Opening AR/AP party-level,
  trial-balance, and P&L behavior remain covered by the existing opening/report
  suites.

## Current Coupling Assessment (Part D compliance)

**COMPLIANT since the 2026-09-29 seam enforcement** (was: NOT compliant — see phase log). Verified by `tests/Feature/Accounting/AdvancedAccountingDisabledTest`: with `double-entry.enabled=false` expense/fund/payment flows work, zero journals, accounting routes 404, and no package-backed service is constructed. Package-ABSENCE safety: host config is package-free, the single `class_exists` probe short-circuits before any package state, all remaining package imports live behind the `advanced-accounting` middleware or inside the integration layer (ManualEntryService / OpeningBalanceService / DoubleEntry gateway / Accounting controllers).

Commerce core (Product/Purchase/Sale/POS/Stock/Batch/Customer/Supplier/Payment/Return/Refund) has no package dependency. Lite profit/COGS/dashboards are package-free.

---

## Test Baseline (recorded BEFORE new changes — 2026-09-29)

`php artisan test` → **271 passed, 1070 assertions, 0 failed, 0 skipped** (28.1s). MySQL reachable.
Note: stale `.phpunit.cache/test-results` previously recorded 230 defects; current run supersedes it — the suite is green now. Do not attribute this green state to any future work.

---

## Phase Log

| Date | Phase | Files changed | Migrations | Tests | Result | Next step |
| --- | --- | --- | --- | --- | --- | --- |
| 2026-09-29 | Audit (Steps 1–4): two-repo status audit, baseline tests | docs only | none | full suite | 271 pass / 0 fail | Step 5: stabilize Lite (ledgers, supplier_payment linkage, COGS single source, seam enforcement) |
| 2026-09-29 | P1 — baseline protection: bd-double-entry committed as `884685e` (43 files, .gitignore added, no secrets; 134 accounting tests green against it) | bd-double-entry repo | none | accounting suites | 134 pass / 0 fail | P2 seam |
| 2026-09-29 | P2+P3 — optional-module seam enforced: `AdvancedAccountingGateway` (renamed/reshaped from FullAccountingGateway, dead event methods dropped) + `NullAdvancedAccountingGateway` + `DoubleEntryAdvancedAccountingGateway`; `AccountingAvailability.available()/enabled()` made the single centralized decision; container binds by availability; `advanced-accounting` middleware gates all `admin.accounting.*` routes (404 when off); sidebar menu gated by `enabled()`; Expense/Fund controllers stripped of ALL package references (`ManualEntryService`→gateway, `SourceType::EXPENSE`→`expenseHasJournal`, `ManualPostingResult` holds `journalNo` string not a JournalEntry); `config/double-entry.php` made package-free (AccountRole constants → plain strings) so a package-absent boot cannot fatal; old `FullAccountingGateway`/`NullFullAccountingGateway` deleted; `PaymentCollectionService` repointed | app/Services/Accounting, app/Support/Accounting, app/Providers, app/Http/Middleware(new), app/Http/Controllers/Admin/{Expense,Fund}Controller, config/double-entry.php, bootstrap/app.php, routes/web.php, master.blade.php, tests | none | new `AdvancedAccountingDisabledTest` (8) + full suite | **279 pass / 0 fail** (1099 assertions) | P4 — Lite stabilization: CogsCalculator, supplier_payment trace, Lite ledgers |
| 2026-09-29 | P4.1 — COGS stabilized on ONE shared rule: new `App\Services\CogsCalculator` (Lite-owned, zero package knowledge): stored realized line-total `cogs` first, line snapshot `purchase_price × qty` otherwise, live product price never used; recognition = delivered/completed + created_at window (updated_at forbidden as recognition date — unrelated edits would move COGS between periods); period basis documented as interim because NO delivery timestamp exists on orders (CURRENT LIMITATION reported, no migration made this task). AccountsController month-profit, ReportController profitLoss and DashboardController today-profit all rebuilt on it (sales + COGS from the SAME recognized set; Accounts/Dashboard period basis switched from updated_at to created_at — behavior change by design). `StockController::cogs()` left alone: a stock-side view, not a competing profit formula | app/Services/CogsCalculator.php(new), AccountsController, ReportController, DashboardController | none | new `tests/Feature/CogsCalculatorTest` (10: stored-COGS authority + line-total interpretation, historical stability vs product price change, period boundaries, status filter, created_at recognition proof incl. touch() invariance, AccountsController parity, cross-controller same-COGS invariant, explicit legacy fallback (live price never used), decimal precision 12.35×3=37.05 / 370.50 no drift, return/refund no-double-reverse) + full suite | **289 pass / 0 fail** (1130 assertions; baseline was 279/1099) | P4.2 — supplier_payment fund-row traceability, then Lite Customer/Supplier ledgers |
