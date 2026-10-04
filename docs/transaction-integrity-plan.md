# Transaction Integrity Plan

Status: Phase 0 audit complete; implementation is intentionally staged and fail-closed.
Repository: `lara`
Related package: `../bd-double-entry`
Branch audited: `premium`
Date: 2026-10-04

## 1. Current financial transaction types

The host currently represents financial activity through:

- `Expense` + linked `FundTransaction`; optional Advanced EXPENSE journal.
- Manual money-in / other income and owner capital through `FundTransaction`;
  Advanced posting uses `ManualEntryService` and distinct nature values.
- Owner withdrawal through `FundTransaction`; Advanced OWNER_DRAWINGS journal.
- Customer sale/order through `Order`, `OrderDetails`, order payment history,
  legacy payment rows, stock allocation/COGS, fund rows, and Advanced SALE /
  SALE_COGS journals.
- Customer payment through `OrderPayment`, synchronized payment records,
  fund row, due recalculation, and Advanced customer settlement journal.
- Purchase through `Purchase`, `PurchaseItem`, stock batches, supplier due,
  supplier payments/fund rows, and Advanced PURCHASE journal.
- Supplier payment through `SupplierPayment`, fund row, supplier totals/due,
  and Advanced supplier settlement journal.
- Refund through `Refund`, order/customer state, fund debit/reversal behavior,
  and possible Advanced refund journal.
- Supplier return through `SupplierReturn` / items and stock-in operations.
- Fund transactions for sale, refund, expense, supplier payment, salary/bonus,
  withdrawal, warranty, manual money-in, and related operational sources.
- Opening balances through the dedicated `OpeningBalanceService`; these are
  explicitly outside generic purge.
- Advanced journals and journal lines, including reversal relationships.

## 2. Current delete/edit routes and actions

Financially relevant destructive or mutating paths found:

- `routes/web.php`: `/fund/{id}` -> `FundController::destroy`.
- `routes/web.php`: `/expenses/{id}` -> `ExpenseController::destroy`.
- `routes/web.php`: `/refunds/{id}` -> `RefundController::destroy`.
- `routes/web.php`: `purchases/{id}` -> `PurchaseController::destroy`.
- `routes/web.php`: `purchases/drafts/{id}` -> `PurchaseController::destroyDraft`.
- `routes/web.php`: `order/destroy` -> `OrderController::destroy`.
- `IncompleteOrderController::destroy` deletes incomplete orders.
- `SupplierPayment` has lifecycle side effects on create/delete, but no dedicated
  protected deletion route was found in the initial route scan.
- Expense edit/update and fund edit/update exist; Expense already treats
  system-generated warranty categories as non-editable.
- Purchase and order editing/status flows can change operational records and must
  be separated into draft/unposted versus posted/settled states.
- Non-financial master-data deletes (products, categories, customers, suppliers,
  etc.) are out of scope unless a financial history mutation is demonstrated.

All direct financial deletes must be reviewed before removing or redirecting them.
Normal pages must not expose a hard-delete escape hatch.

## 3. Existing reversal capabilities

Advanced Accounting provides:

- Immutable POSTED/REVERSED journals.
- `ReversalService` with mirrored lines, transactional updates, idempotency,
  double-reversal protection, and `reversal_of_id` relationships.
- Source type, source ID, posting key, party, actor, and metadata traceability.
- Host cancellation/full-return flows already use immutable reversals for supported
  SALE and SALE_COGS cases.
- Correction policy remains Original -> Reversal -> Correct transaction.
- Lite operational refund/reversal behavior exists but is not a universal
  transaction correction primitive and must not be assumed equivalent.

## 4. Dependency graph by transaction

### Expense / income / capital / withdrawal

Source row -> optional `FundTransaction` -> optional Advanced journal + journal lines.
Expense may additionally be warranty/damage generated and linked to operational records.
Purge is potentially implementable only for standalone manual records with all
dependencies resolved and no reversal/settlement chain.

### Customer payment

`OrderPayment` -> order/customer due recalculation -> synchronized payment
representation where present -> `FundTransaction` -> Advanced settlement journal.
Any refund, return, reversal, or downstream order state blocks generic purge until
a complete safe handler exists.

### Supplier payment

`SupplierPayment` -> purchase/supplier due and `total_paid` side effects ->
`FundTransaction` -> Advanced AP settlement journal. Payment date is the
authoritative business date. Supplier payment purge must reverse all denormalized
supplier totals atomically.

### Sale

`Order` -> `OrderDetails` -> stock allocations/batches and persisted COGS ->
payment history/legacy payment -> fund rows -> refunds/returns -> warranties and
shipping -> SALE and SALE_COGS journals. Generic sale purge is BLOCKED by default.

### Purchase

`Purchase` -> `PurchaseItem` -> `StockBatch` and pricing/warranty rows ->
supplier payments/due -> purchase logs -> PURCHASE journal. Generic purchase
purge is BLOCKED by default, especially where stock remains or was sold.

### Refund / return

Refund -> order/customer -> fund debit/reversal -> possible journal and return
state. Returns additionally affect stock and COGS. Generic purge is blocked until
a dedicated dependency-safe handler is proven.

### Opening balance

Opening balance draft/posting, party balances, cash/inventory/equity lines, and
cutover state are owned by the dedicated opening workflow. Generic purge is
forbidden.

## 5. Source-of-truth versus derived records

Source-of-truth records are the business event rows: Expense, OrderPayment,
SupplierPayment, Order, Purchase, Refund/Return, and explicitly entered fund
transactions where no operational source exists.

Derived or synchronized records include:

- FundTransaction rows generated from operational events.
- Order paid/due/payment-status fields.
- Supplier total_paid/current_due fields.
- Product stock denormalized from stock batches.
- Persisted order-detail COGS after stock allocation.
- Advanced journals and lines, which are accounting representations of the event.
- Activity/expense logs and audit snapshots.

A purge handler must not delete a source while leaving any representation or
denormalized state inconsistent.

## 6. Lite accounting dependencies

Lite Fund/ FundTransaction is a virtual single till. Customer ledgers derive from
orders and order-payment history. Supplier ledgers derive from purchases and
supplier payments. Expense reports read Expense. Profit calculations use the
shared CogsCalculator and operational records. Reconciliation after a purge must
recalculate payment totals, supplier totals/due, fund balances, and report inputs.

## 7. Advanced accounting dependencies

The package owns Account, JournalEntry, JournalLine, posting, idempotency, source
and party traces, reports, and reversal mechanics. Posted journals are immutable.
No ordinary controller may call model delete on posted journals. A privileged
administrative journal purge service may be added in the package only if the host
orchestrator has already proven the complete dependency graph and explicitly calls
it.

When Advanced is disabled or absent, Lite purge handlers must remain package-free.
Opening/cutover state and package migrations must be checked before any Advanced
purge is considered.

## 8. Stock, inventory, and COGS

Stock batches are the stock source of truth; product stock is denormalized.
Stock-affecting sales, purchases, returns, and refunds are unsafe for generic
purge unless a complete allocation-aware rebuild is implemented. The default
policy is BLOCKED with reversal required. No handler may directly mutate product
stock to make a purge appear balanced.

## 9. Customer/supplier dues and payments/refunds

Customer due is recalculated from OrderPayment history. Supplier due and
total_paid have operational side effects. Purge handlers must lock the source and
party rows, remove/reconcile all linked representations, and recalculate rather
than apply guessed deltas. Existing refunds, returns, or later payments block the
simple payment handlers.

## 10. Unsafe deletion cases

The following fail closed:

- Unknown source type or unresolved dependency.
- Sale or purchase with stock, COGS, payment, return, refund, warranty, or journal
  dependencies not handled end-to-end.
- Any opening balance or pre-cutover dependency.
- Original/reversal journal chains unless the entire chain is proven safe.
- System-generated warranty expenses with operational links.
- Any transaction older than the configured hard-delete window.
- Any transaction whose authoritative business date cannot be resolved.
- Advanced package state unavailable or inconsistent when an Advanced representation
  exists.
- A dependency appearing between preview and execution.

## 11. Proposed purge architecture

New host services:

- `TransactionEffectiveDateResolver`: one authoritative business-date rule.
- `FinancialTransactionDependencyResolver`: normalized dependency graph,
  source identity, journals, payments, refunds, returns, stock, parties, and
  blockers.
- `TransactionPurgeEligibilityService`: window, actor, source, dependency,
  reversal, cutover, and package-state checks; fail closed.
- `FinancialTransactionPurgeService`: `preview()` and atomic `purge()`;
  re-resolves and re-checks everything inside the transaction.
- Handler contract with initially safe handlers for standalone Expense,
  Other Income, Capital, Withdrawal, and only payment types proven by tests.
  Sale/Purchase handlers start blocked.
- Optional package-side `AdministrativeJournalPurgeService`, callable only
  from the host orchestrator and never from normal posting code.

New protected route namespace:

- `GET admin/transaction-control` for the Super Admin-only center.
- Preview/detail endpoint.
- POST purge endpoint; never GET.
- Purge-history endpoint with no delete action.

## 12. Authorization architecture

Reuse Spatie permissions and the repository's existing admin guard/role model.
Add an explicit `purge-financial-transactions` permission and assign it only
through the established Super Admin mechanism after that mechanism is audited.
Use middleware/policy server-side; UI hiding is supplementary. Do not use
user ID assumptions.

Purge requires current-password verification with `Hash::check`, a minimum
reason length, confirmation phrase, CSRF, authentication, and rate limiting.
Neither password nor failed password material is stored in logs or audit data.

## 13. Window and effective-date policy

Add to accounting config:

`transaction_hard_delete_window_days = (int) env('ACCOUNTING_HARD_DELETE_WINDOW_DAYS', 30)`

Add the key to `.env.example` only. Do not modify the real `.env`.

Use the resolver, not `updated_at`:

- Expense: `expense_date`.
- SupplierPayment: `payment_date`.
- OrderPayment: canonical payment creation date unless audit proves another field.
- Sale: canonical order recognition/creation date under current schema.
- Purchase: `purchase_date`.
- Refund: processed/accounting date, with an explicit pending/rejected policy.
- Manual fund records: canonical creation date unless a business date is added.

Eligibility is inclusive through the configured boundary using application timezone.
Execution repeats all checks after row locks.

## 14. Immutable purge audit

Add a migration/table `financial_transaction_purge_logs` with transaction type,
reference, source type/id, effective date, amount, party type/id, actor,
performed_at, reason, IP, user agent, dependency snapshot, before snapshot, and
metadata. The audit row is written within the same DB transaction before deletes
and must survive the purge. No password is stored. Audit rows have no delete route.

## 15. Implementation phases

1. Phase 0: this audit and plan; no financial behavior changed.
2. Phase 1: authorization permission, config, effective-date resolver, immutable
   audit migration/model, and protected center read-only preview.
3. Phase 2: dependency graph and eligibility service with fail-closed blockers.
4. Phase 3: safe standalone Expense/manual income/capital/withdrawal handlers;
   remove normal-page hard-delete exposure and preserve legitimate draft editing.
5. Phase 4: customer/supplier payment handlers only after reconciliation tests.
6. Phase 5: advanced administrative journal purge integration, only for handlers
   proven safe in both Advanced ON/OFF modes.
7. Phase 6: keep Sale/Purchase/return/refund purge blocked unless complete
   allocation-aware handlers and regression tests are implemented.
8. Phase 7: dedicated Feature/Unit tests for authorization, password, boundary,
   race/recheck, rollback, audit survival, reconciliation, and package-disabled
   operation.

## 16. Required verification

Before enabling any purge:

- Existing full suite remains green.
- Advanced OFF: Lite works and no package service/model is resolved.
- Advanced ON: journals, lines, party ledger, fund/cash, trial balance, and P&L
  reconcile after each supported purge.
- Password, reason, permission, CSRF, rate-limit, expiry, exact-boundary,
  duplicate, race, rollback, and audit-retention tests pass.
- Sale, purchase, stock, opening-balance, reversal-chain, and unresolved-source
  tests prove they are blocked.

## Audit conclusion

The architecture is safe to implement only incrementally. The first implementation
slice should be read-only Transaction Control preview plus shared authorization,
date, dependency, and audit foundations. Generic deletion of sales, purchases,
refunds, returns, opening balances, stock-linked events, and unresolved Advanced
journals must remain blocked until complete handlers and reconciliation tests exist.

## Phase 1 implementation status (2026-10-04)

Implemented:

- Configurable `ACCOUNTING_HARD_DELETE_WINDOW_DAYS` with a 30-day default.
- Immutable `financial_transaction_purge_logs` table/model foundation.
- Central effective-date resolver and dependency resolver.
- Fail-closed eligibility service with window, system-link, stock, payment, and
  unresolved-chain blockers.
- Permission `purge-financial-transactions` and protected Transaction Control Center.
- Searchable preview for Expense and FundTransaction records plus purge-history view.
- Server-side blocking of normal hard-delete endpoints for sales, posted purchases,
  expenses, and fund transactions. Draft purchase deletion remains separate.

Not enabled yet:

- No destructive purge endpoint is exposed. This is deliberate: the first safe
  slice must not delete an accounting representation until a handler can prove
  Lite/Advanced, due, fund, stock, and journal reconciliation. The center currently
  identifies eligible-looking records but displays preview-only status until the
  handler and password-confirmation tests are complete.

## Phase 2A implementation status (2026-10-04)

Executable purge is now enabled only for `expense`, `other_income`,
`owner_capital`, and `owner_withdrawal`. It requires the protected permission,
current password, a 10-character reason, exact `DELETE TYPE-ID` confirmation,
rate limiting, row re-checks inside a database transaction, dependency
re-resolution, before/dependency snapshots, and an immutable audit row.

The Advanced journal bridge is isolated in `bd-double-entry`'s
`AdministrativeJournalPurgeService`; ordinary posting and reversal workflows
retain journal immutability. Lite-only mode purges supported operational rows
without resolving package classes. Payments, sales, purchases, refunds,
returns, COGS, opening balances, manual journals, stock-linked rows, and
reversal chains remain blocked.

## Phase 2B-0 payment readiness audit (2026-10-04)

Customer and supplier payment purge remain read-only and blocked. The audit is
documented in `docs/payment-purge-readiness.md`. A read-only
`PaymentPurgeReadinessService` now reports payment dependencies and blockers;
it does not authorize or delete payments. The principal customer blocker is
that Lite fund credits are order-level `sale` rows rather than
`OrderPayment`-specific rows. Supplier payments have current-path fund links,
but persisted due reconciliation and supplier-return/AP behavior are not yet
proven safe. No payment purge handler was added.
