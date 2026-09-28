# SoftMit Commerce — Accounting Architecture

Two-level accounting: **Lite** (always available, operational) and **Full**
(`softmit/bd-double-entry`, optional professional double-entry).

```text
                    SOFTMIT COMMERCE
                           │
                     BUSINESS CORE
                           │
           ┌───────────────┴────────────────┐
           ▼                                ▼
    LITE ACCOUNTING                  BUSINESS EVENTS
    ALWAYS AVAILABLE                (stable contracts)
           │                                │
           │                    ┌───────────┴───────────┐
           ▼                    ▼                       ▼
   FundTransaction       FULL ACCOUNTING          FUTURE VAT
   Customer/Supplier     bd-double-entry          bd-vat-mushak
   Due, Basic Profit     (optional)               (optional)
                                │
                                ▼
                    Journals → GL → Trial Balance
                       → Income Statement / Balance Sheet
```

## The non-negotiable rule

Commerce and Lite Accounting must work correctly when Full Accounting is
**disabled, not installed, or failing**. Ecommerce controllers must never
require package classes at runtime. Today this is *not* fully guaranteed
(see "Current gaps"); the `FullAccountingGateway` + NullObject contract is the
mechanism that will enforce it.

## Ownership boundary

### LARA owns (operational truth)

Products, categories, brands, customers, suppliers, employees, vendors,
resellers, `orders` / `order_details`, purchases, stock + `stock_batches`,
expenses, refunds/returns, **`fund_transactions`** (Cash/Bank movements),
customer due (`orders.due_amount`), supplier due (`suppliers.current_due`,
`purchases.due_amount`), and **COGS** (`order_details.cogs`, realized by
`StockManagementService` FIFO/LIFO/AVG batch allocation).

### BD-DOUBLE-ENTRY owns (financial truth)

`accounting_accounts` (Chart of Accounts), `accounting_journal_entries`,
`accounting_journal_lines`, `accounting_fund_accounts` (Fund→GL map),
`accounting_sequences` (journal numbers), `accounting_posting_failures`,
posting rules, General/Sub ledgers, Trial Balance, Income Statement,
Balance Sheet (planned), opening balances, reversals, periods (planned).

The package never imports `App\Models\*` — parties are resolved through the
config `double-entry.parties` class-string map; actors through
`auth.providers.users.model`. It never duplicates customer/supplier master
data (`party_type` + `party_id` only) and never reimplements FIFO/LIFO/AVG —
it receives operational COGS as an amount.

### Dependency direction (only legal arrows)

```text
LARA commerce ──► contracts/events ──► bd-double-entry
LARA bridge (App\Services\Accounting) ──► package services
package ──✗── LARA models        (statically; class-string config only)
```

## Cash movement ≠ accounting classification

Lite rule (§23 of the codex): Fund **IN** is not automatically Income and
**OUT** is not automatically Expense.

| Fund transaction source | Cash | Correct classification |
|---|---|---|
| `sale` | In | Revenue (recognized on delivered/completed) |
| `refund`, `order_refund` | Out | Contra-revenue |
| `expense` | Out | Expense |
| `supplier_payment` | Out | Liability settlement (NOT expense) |
| `employee_salary` / `employee_bonus` | Out | Expense |
| `manual_add` | In | Owner capital / deposit (NOT income) |
| `withdraw` | Out | Owner drawings (NOT expense) |
| `warranty`, `warranty_resell` | In | Other income |
| `refund_reversal` | In | Cash recovery (NOT income) |

## COGS — one operational truth

```text
StockManagementService (FIFO/LIFO/AVG over stock_batches)
        ↓ realized on delivery (OrderStatusService, POS, mobile API)
order_details.cogs  (+ batch_ids traceability)
        ├────────► Lite Basic Profit Summary (accounts dashboard, reports)
        └────────► Full Accounting journal: Dr COGS / Cr Inventory
```

Fallback when `cogs` is unset (pre-feature data / negative-stock paths):
`order_details.purchase_price`, then `product.purchase_price`. Both Lite
surfaces use this identical chain. Full accounting never recomputes COGS.

Sale recognition for Lite profit: `order_status IN (delivered, completed)`;
periods filter on `created_at`; refunds netted as contra-revenue.

## Posting engine invariants (package)

- **Balance**: `SUM(debit) = SUM(credit)` checked with bcmath at scale 2
  (`Money`); unbalanced is fatal on `post()`, tolerated on draft `save()`.
- **Atomicity**: journal + lines + number allocation in one `DB::transaction`.
- **Idempotency**: deterministic `posting_key` (e.g. `sale:500:revenue`,
  `expense:400`, `opening:balances`) with a DB unique index plus duplicate-key
  race fallback — retry of the same event can never duplicate a journal.
- **Journal numbers**: `JV-2026-000042` from a row-locked `accounting_sequences`
  table (never `last()+1`).
- **Immutability**: posted journals cannot be edited/deleted (model-level
  guard); corrections = `ReversalService` mirror journal
  (`reversal_of_id`, `reversed_by`, `reversal_reason`).
- **Traces**: every journal carries `source_type/source_id/reference`,
  `party_type/party_id` (PARTY = who the money is about) and
  `created_by/posted_by/reversed_by` (ACTOR = who did it). Actor and party
  are distinct.
- **No stored balances**: account balances are always derived from posted
  journal lines (`LedgerService`, `TrialBalanceReport`, `ProfitAndLossReport`).

## Cutover

`double-entry.cutover_date` (default `2026-10-01`):

- **Before cutover** → summarized as one Opening Balance journal
  (`App\Services\Accounting\OpeningBalanceService`: cash as actually
  collected, receivables from live order dues, payables from supplier dues,
  stock valued from batches, explicit balancing line).
- **After cutover** → per-event automatic journals.
- `JournalPoster::assertDateIsPostable()` refuses non-OPENING/MANUAL journals
  dated before cutover (double-counting guard).
- Historical backfill is intentionally out of scope.

## Failure handling (sync states)

Enabled-but-failing posting is **never** silently swallowed — and never
blocks commerce money operations:

```text
Commerce succeeded + posting failed
   → accounting_posting_failures row (unique posting_key, attempts, payload)
   → state PENDING/FAILED, operator sees "needs entry" (ManualEntryService::needsEntry)
   → safe retry (idempotent posting key) → SYNCED, failure resolved
```

`NullFullAccountingGateway` (planned) covers the *intentionally disabled /
package absent* case only — a different state from "enabled but failed".

## Reconciliation (Lite ↔ Full)

| Lite (operational) | Full (GL) |
|---|---|
| Customer due (`orders.due_amount`, delivered only) | Accounts Receivable party balance |
| Supplier due (`suppliers.current_due`) | Accounts Payable party balance |
| Fund balance (`FundHelper::balance()`, collected cash only) | Cash/Bank GL via `accounting_fund_accounts` |
| Stock batch value (`remaining_qty × unit_cost`) | Inventory GL |
| Basic Profit Summary | Income Statement |

Differences must be **reportable, never auto-corrected**. Legitimate
differences: pre-cutover history, timing (recognition date vs posting date),
manual journals.

## Menus / UI split

- **Accounts (Lite)** — always visible: dashboard, funds, expenses,
  customer/supplier activity, Basic Profit Summary.
- **Full Accounting** — visible only when enabled: Chart of Accounts,
  Journals, GL, Party Ledger, Trial Balance, Income Statement, Opening
  Balances (Balance Sheet planned). Entire menu disappears when disabled.

## Current gaps (honest status)

1. No feature flag / gateway contract yet — host controllers/services import
   package models directly; removing the vendor package fatals expense/fund
   routes. → Phase 6 (`FullAccountingGateway` + Null + availability check).
2. No domain events yet (`app/Events` empty); sales/purchases/payments are not
   journalled automatically. Only Expense / Income / Capital / Withdrawal
   dual-write through `ManualEntryService`. → Phases 8–10.
3. Lite customer/supplier ledgers and Balance Sheet + fiscal periods not built.
4. `fund_transactions.balance_before/after` are legacy write-mostly columns,
   never read by UI; balances are always derived (see §"No stored balances").
