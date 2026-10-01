# Accounting Architecture — Lite vs Advanced (Official)

Companion to `docs/architecture.md` (ownership boundaries + gap list) and `docs/accounting-progress.md` (status source of truth). This file is the **normative** definition of the two-level architecture and the terminology. Where `docs/architecture.md` uses "Full Accounting", the official name from now on is **Advanced Accounting**.

## Terminology (Part C)

- **Lite Accounting** — operational/basic accounting, built into ecommerce core, always available. Never called "fake accounting".
- **Advanced Accounting** — optional professional double-entry module. Engine: `softmit/bd-double-entry`.
- Advanced Accounting does **not replace** Lite Accounting; it is additive.

### Lite transaction semantics

`TransactionCategory` is Lite-owned and describes **what business event
happened**. It is separate from `direction` (how money moved), `source` and
`source_id` (the legacy originating record), `nature`, party, and fund. It
supplements legacy fields and never encodes debit/credit rules. The category
column is nullable so historical rows that cannot be classified without
guessing remain truthful.

Lite party ledgers are operational views: customer balances come from orders
and order-payment history, while supplier balances come from purchases and
supplier-payment history. They deliberately do not read Advanced journal or
subledger tables. Their closing balances must reconcile to the existing
operational due fields; the due fields remain authoritative.

### Advanced integration boundary

The first supported Advanced event slice is Expense, Other Income, Owner
Capital, and Owner Withdrawal. Lite categories and business rows remain
host-owned; the adapter selects package account roles while the package owns
journal mechanics, posting, idempotency, and reports. Payment, purchase, sale,
COGS, return, and refund postings remain intentionally deferred.

Advanced live posting has an explicit readiness boundary: package availability
and the feature flag are insufficient. `readyForLivePosting()` is true only
after the configured cutover date exists and the deterministic
`opening:balances` journal is POSTED and balanced. Opening covers records before
the cutover date; live events are eligible from the cutover date at midnight.

## The core rule (Parts D–E)

```
Advanced Accounting OFF → Commerce works → Lite Accounting works
Advanced Accounting ON  → same commerce transactions ADDITIONALLY produce
                          balanced, traceable journals
```

Every commerce feature (Product, Purchase, Sale, POS, Stock, Batch, Customer, Supplier, Payment, Return, Refund) and every Lite feature (Fund, FundTransaction, Income, Expense, Customer Due, Supplier Due, Basic Ledgers, Basic Profit) MUST function with the package absent.

## Layered structure (Part B)

```
SOFTMIT COMMERCE
├── COMMERCE CORE (lara)          Product, Category, Brand, Customer, Supplier,
│                                 Purchase, Sale, POS, Stock, Batch, Payment,
│                                 Return, Refund
├── LITE ACCOUNTING (lara)        Fund, FundTransaction, Cash In/Out, Income,
│                                 Expense, Customer/Supplier Due,
│                                 Basic Ledgers, Basic Profit Summary
└── ADVANCED ACCOUNTING (optional, softmit/bd-double-entry)
                                  CoA, Journal Dr/Cr, GL, Party Ledger, AR/AP,
                                  Cash&Bank, Inventory/COGS accounting, TB, IS,
                                  BS, Capital, Drawings, Opening Balance,
                                  Adjustments, Reversal, Fiscal Period, Audit Trail
```

## Ownership

- **lara owns Lite Accounting concepts** — Fund, FundTransaction, Income, Expense, Due, basic ledgers, basic profit. These NEVER move into the package.
- **bd-double-entry owns accounting concepts only** — Account, JournalEntry, JournalLine, posting, GL, party ledger, TB/IS/BS, reversal, periods, audit trail. It is host-agnostic; business meaning enters via 4 config extension points (`parties`, `source_routes`, `actor_guards`, `cutover_date`).
- **Commerce owns business masters.** The package MUST NOT create `accounting_customers` / `accounting_suppliers` / `accounting_products`; it references them via `source_type/source_id` and `party_type/party_id` (closed enums + host whitelist config).

## Integration seam (Parts F–G)

One centralized abstraction — no scattered `class_exists()` / `config()` checks, no package model imports in core controllers:

```
interface AdvancedAccountingGateway {
    public function available(): bool;   // package installed & migrated
    public function enabled(): bool;     // available AND turned on
    // posting operations, expanded only as needed
}
```

- Implementation when OFF / unavailable: `NullAdvancedAccountingGateway` (no-ops; commerce continues).
- Implementation when ON: adapter that builds `JournalDraft`s and calls `JournalPoster::post()` (via `PostingFailureLogger::attempt()` — accounting errors must never break commerce, must never silently lose a posting).
- Current code state: `app/Support/Accounting/AccountingAvailability` + `app/Services/Accounting/FullAccountingGateway` / `NullFullAccountingGateway` exist but the seam is **inert** (see progress doc). The refactor target is to merge these into the single gateway above and route ALL package touchpoints (ManualEntryService, OpeningBalanceService, accounting controllers/menu, ExpenseController's package import) through it. Rename to `AdvancedAccounting*` per official terminology when touched.

## Event flow (Part K)

Controllers never create journals directly. Target flow:

```
Commerce operation → Business event → { Lite logic (always) , Advanced posting (gateway, optional) }
```

Today: no events exist; posting is synchronous controller/service calls. Domain events are introduced during integration (Step 10), reusing `PaymentCollectionService` as the single collect-money primitive.

## Cash movement doctrine (Parts T, R, S)

```
Cash In  ≠ Income      Customer collection, owner capital = Cash In, NOT income
Cash Out ≠ Expense     Supplier payment, owner withdrawal = Cash Out, NOT expense
Capital  ≠ Income      Withdrawal ≠ Expense
```

Lite: FundTransaction direction + source encode cash movement; `nature` (owner_capital / other_income) is mandatory for manual entries; Advanced side maps capital→OWNER_CAPITAL, withdrawal→OWNER_DRAWINGS.

## Same event, two views (Parts L–Q)

| Event | Lite | Advanced |
| --- | --- | --- |
| Cash sale 10k (COGS 7k) | Fund IN 10k | Dr Cash / Cr Sales Revenue 10k; Dr COGS / Cr Inventory 7k |
| Credit sale | Customer Due + | Dr AR / Cr Sales Revenue (+ COGS legs) |
| Customer pays 4k | Fund IN 4k, Due −4k | Dr Cash / Cr AR — NOT new revenue |
| Credit purchase | Stock +, Supplier Due + | Dr Inventory / Cr AP |
| Supplier pays | Fund OUT, Due − | Dr AP / Cr Cash |
| Expense | Expense + Fund OUT | Dr Expense / Cr Cash-Bank |
| Capital | Fund IN (nature=capital) | Dr Cash / Cr Owner Capital |
| Withdrawal | Fund OUT (drawings) | Dr Owner Drawings / Cr Cash |

## COGS — one source (Part U)

Inventory engine (StockManagementService FIFO/LIFO/AVG + batch allocation) is the ONLY cost producer: it persists the realized line total into `order_details.cogs` at stock-out. Reading that cost into profit is the job of the single shared rule **`App\Services\CogsCalculator`** (`lineCogs` / `cogsForOrders` / `recognizedOrders` / `periodProfit`) — Accounts dashboard, ReportController P&L and Dashboard today-profit all consume it; no controller may re-implement a competing loop. `cogs` (a LINE TOTAL, not a unit cost) is authoritative; the line's own `purchase_price × qty` snapshot is the only fallback; the live `Product.purchase_price` is never consulted. Recognition is `order_status ∈ {delivered, completed}` + a `created_at` window (no delivery timestamp exists yet — see CURRENT LIMITATION in `CogsCalculator`); `updated_at` must never be a recognition date. Advanced consumes recognized COGS and NEVER recomputes FIFO/LIFO/AVG.

## Profit (Parts V–W)

- Lite Basic Profit Summary: `Sales − COGS = Gross; + Other Income − Operating Expenses = Net`. Excludes capital, due collections, supplier payments, drawings. (Implemented in `ReportController::profitLoss`.)
- Advanced Income Statement: derived ONLY from POSTED journal lines (`ProfitAndLossReport` — already compliant).
- The two must reconcile while Advanced is ON.

## Ledgers & reconciliation (Parts X–Z, Step 12)

- Lite customer/supplier ledger: operational Date/Type/Reference/Debit/Credit/Balance from orders/purchases + payments — usable with Advanced OFF. **Not yet built.**
- Advanced party ledger: AR/AP sub-ledgers via `LedgerService::forParty()` — built.
- Reconciliation targets: Customer Due ↔ AR, Supplier Due ↔ AP, Fund ↔ Cash/Bank GL (`FundAccount` mapping — Lite funds map to Advanced accounts only when Advanced is enabled), Inventory ↔ Inventory GL, Basic Profit ↔ Income Statement.

## Phase 3–4 recognition and settlement boundary

Posted sales use SourceType::SALE and the full order amount:
Dr ACCOUNTS_RECEIVABLE / Cr SALES_REVENUE. Posted purchases use
SourceType::PURCHASE and the full committed purchase amount:
Dr INVENTORY / Cr ACCOUNTS_PAYABLE. Recognition is gated until the opening
journal is posted. Payment settlement, COGS, returns, refunds, and tax remain
separate integrations.

Each committed OrderPayment posts one customer settlement:
Dr default Fund / Cr Accounts Receivable. Each committed SupplierPayment posts
Dr Accounts Payable / Cr default Fund. Payment rows, not order/purchase IDs,
are the settlement source IDs, so multiple payments remain independently
traceable and idempotent. Credit is never a Fund; it remains AR/AP. The
current Lara Fund model is a single virtual till, so per-Cash/Bank/MFS GL
mapping is intentionally deferred.

COGS is sourced exclusively from the stock engine's persisted order-detail
COGS line totals and batch IDs. Advanced COGS is requested only after stockOut
succeeds, and uses SALE_COGS/order ID independently of the SALE journal.
Fallback stock decrement can preserve Lite operation but does not provide an
authoritative Advanced COGS amount. Cancellation and return flows currently
clear operational COGS without reversing posted Advanced sale/COGS journals;
the next return/reversal phase must address that before those workflows are
Advanced-integrated.

## Invariants

1. Every posted journal: Σ Debit = Σ Credit (bcmath exact). No exception.
2. Posted journals immutable; corrections via Original + Reversal + Correct journal.
3. Idempotent posting keys (`sale:{id}:revenue`, `customer-payment:{id}`, …) backed by DB uniqueness; same event never double-journals.
4. Account resolution by semantic **role** (`AccountRole`, 30 keys incl. cash, bank, accounts_receivable, inventory, accounts_payable, sales_revenue, cogs, owner_capital, owner_drawings) — never by hardcoded DB IDs.
5. Traceability chain: Amount → JournalLine → Journal → Source → Party → Actor.
6. Cutover: no historical backfill; pre-cutover summarized into one Opening Balance journal; automatic journals only after `double-entry.cutover_date`.

## Required two-mode testing (every accounting phase)

- **Mode A** `Advanced OFF`: Commerce ✓ Lite ✓ no package dependency ✓
- **Mode B** `Advanced ON`: Commerce ✓ Lite ✓ journals post ✓ Lite↔Advanced reconcile ✓

## Out of scope (current phase)

VAT/Mushak (`bd-vat-mushak`) is the NEXT major phase — do not mix into accounting stabilization. Purchase-page quick-create (Supplier/Product/Category/Brand) is an ecommerce (lara) feature — never implemented inside the package.
