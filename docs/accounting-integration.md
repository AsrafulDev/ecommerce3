# Lara ↔ Full Accounting Integration (current state)

Companion to `docs/architecture.md`. This file documents the **actual**
integration in this application as of the premium branch, including what is
deliberately not wired yet.

## Bridge layer

```text
app/Services/Accounting/ManualEntryService.php   dual-write for manual money flows
app/Services/Accounting/OpeningBalanceService.php cutover worksheet → opening journal
app/Support/Accounting/ManualPostingResult.php    per-row posting outcome for UI
config/double-entry.php                           published + host-extended config
app/Http/Controllers/Admin/Accounting/*           Full Accounting UI (CoA, journals,
                                                  ledgers, TB, P&L, opening, reports)
```

Package engine: `softmit/bd-double-entry` (path repo `../bd-double-entry`,
symlinked). See `../bd-double-entry/docs/integration-laravel.md` for the
engine's contract.

## Which business flows journal today

| Flow | Lite record | Full journal | Posting key | Status |
|---|---|---|---|---|
| Expense created | `expenses` + `fund_transactions` (out, `expense`) | Dr expense role / Cr fund cash account | `expense:{id}` (auto-suffix on re-post) | **wired** (`ManualEntryService::expense`, from `ExpenseController`) |
| Money in — nature `owner_capital` | fund row (in, `manual_add`) | Dr cash / Cr `OWNER_CAPITAL` role | `owner_capital:{txId}` | **wired** (`moneyIn`, `FundController`) |
| Money in — nature `other_income` | fund row (in, `manual_add`) | Dr cash / Cr `OTHER_INCOME` role | `income:{txId}` | **wired** (`moneyIn`) |
| Withdrawal | fund row (out, `withdraw`) | Dr `OWNER_DRAWINGS` / Cr cash | `owner_withdrawal:{txId}` | **wired** (`withdrawal`) |
| Opening balances (pre-cutover) | — | one balancing draft → `opening:balances` | `opening:balances` | **wired** (`OpeningBalanceService`, posted once via UI worksheet) |
| Sale / credit sale | fund rows (`sale`), `orders.due_amount`, `order_details.cogs` | — | `sale:{id}:revenue`, `sale:{id}:cogs` (planned) | **NOT journalled yet** — Phase 10 |
| Customer payment | fund row + due reduction | — | `customer-payment:{id}` (planned) | **NOT yet** — Phase 8 |
| Purchase | stock batches, `purchases.due_amount`, `suppliers.current_due` | — | `purchase:{id}` (planned) | **NOT yet** — Phase 9 |
| Supplier payment | fund row (out, `supplier_payment`) | — | `supplier-payment:{id}` (planned) | **NOT yet** — Phase 8 |
| Refund / sales return / purchase return | fund rows, stock batches | — | planned | **NOT yet** — Phases 9–10 |
| Salary / bonus | fund rows (out) | — | planned | **NOT yet** — Phase 11 |

No `app/Events` exist yet; today's dual-write is direct (controller →
`ManualEntryService`). The target architecture replaces this with business
events consumed by independent Lite and Full listeners.

## Cash-flow → semantics map

`FundController` now asks for a **nature** (`owner_capital` |
`other_income`) on money-in; nature is persisted in the journal `metadata`
and read back with `ManualEntryService::naturesFor()`. Legacy rows without a
journal show no nature. Fund `withdraw` is always Owner Drawings.
The Accounts dashboard labels these as Capital / Drawings, never Income /
Expense (Cash In ≠ Income, Cash Out ≠ Expense).

## Fund → GL account mapping

`accounting_fund_accounts` maps a fund key to a GL account;
`config('double-entry.default_fund')` is `'default'`. Lite fund
(`fund_transactions`) stays the operational till; the GL cash account is the
financial truth that must *reconcile* with it after cutover. Expense money-in
uses `ManualEntryService::cashAccount()`.

## Operational values feeding the journal

- **COGS:** `order_details.cogs` (batch-realized by `StockManagementService`
  at delivery; fallback snapshot `purchase_price`, then product price).
  Full accounting must post *this number*, never recompute it.
- **Customer due:** `orders.due_amount` (recomputed by
  `Order::recalculatePaymentTotals()`); receivable is recognized only for
  delivered/completed orders.
- **Supplier due:** `suppliers.current_due` (+ per-`purchases.due_amount`;
  double bookkeeping — reconcile before cutover worksheet posts).
- **Stock value:** `SUM(stock_batches.remaining_qty × unit_cost)` where
  `type = 'in'` (authoritative; `products.stock × purchase_price` is display
  only).

## Recognition statuses

- **Sale recognized** (Lite profit + future journal): `order_status IN
  ('delivered','completed')`; `updated_at` used as delivery-date proxy on the
  monthly dashboard, `created_at` in report periods.
- **Purchase recognized**: on `PurchaseController::store` (stock-in + supplier
  due immediately).
- **Refund**: separate flows — `RefundController` (refunds table, fund out
  `refund`) and order payment-status toggle (fund out `order_refund`); both
  are keyed to avoid collisions. Sale-return semantics for journals must follow
  actual current behaviour when Phase 10 wires them.

## Cutover

`ACCOUNTING_CUTOVER_DATE` (default `2026-10-01`):

- Business events dated before it are refused by the engine
  (`assertDateIsPostable`) — they are represented by the opening worksheet.
- `ManualEntryService` saves pre-cutover rows as `preCutover` (no journal),
  so no money entry is lost, it is simply deferred to the worksheet.
- Opening worksheet is drafted in the UI, only balanced on operator request,
  and posts exactly once under `opening:balances` (drafts invisible to
  reports until posted).

## Failure & retry

- Every dual-write runs **inside** the existing commerce transaction but
  cannot block money operations: posting errors are recorded via
  `PostingFailureLogger` (`accounting_posting_failures`, unique per
  posting_key, attempts, payload) and surfaced in the UI
  (`ManualPostingResult`, `needsEntry()` badges).
- Retry = re-run of the same posting key ⇒ idempotent, no duplicate journal.
- Posted source rows are locked (`blockingJournal*` checks prevent editing
  legacy rows whose journal is posted; reversal unlocks).

## Known gaps / next steps

1. Feature flag + `FullAccountingGateway`/`NullFullAccountingGateway` —
   host controllers still import package classes directly.
2. No domain events; sale/purchase/payment/refund/salary flows unjournalled
   (Phases 8–11 of the master plan).
3. Lite Customer/Supplier ledgers (operational activity lists) not built.
4. Balance Sheet, fiscal periods, retained-earnings closing not implemented.
5. Reconciliation reports (Lite due ↔ AR, fund ↔ Cash GL, batches ↔ Inventory)
   planned as a dedicated screen.
6. `fund_transactions.balance_before/after` are legacy write-mostly columns,
   not read anywhere; all balances are derived — do not start trusting them.
