# Sales reset: safety audit, release readiness and open blockers

Scope: the administrative sales reset at `/admin/transaction-reset` (hidden, no menu link, purge permission,
current admin password). Purchases, payments, salary, expenses and restore are not covered and are not implemented.

## How a sale is reversed

| Current status | Reversal | Ends as |
|---|---|---|
| pending, confirmed, picking, packing, packed | normal cancel transition; stock restored at its recorded batch cost | cancelled |
| completed, delivered | normal return steps (return requested, approved, returned); stock and VAT reversed once, at "returned" | returned |
| shipped, out for delivery, returns in progress, returned, closed, cancelled | refused, with the reason | — |

The reset never forces a status. The order state machine does not allow a direct cancel from completed or delivered,
so those sales end as **returned**, not cancelled.

## Blockers (fail closed)

A reversal is refused, with every reason listed, when any of these hold:

- A refund, a VAT credit note, an existing restock, a warranty claim, a complaint or a digital download refers to the sale.
- The stock batches the sale came from are not recorded, or do not add up to the quantity sold.
- A sold serial number is not recorded as sold from exactly one batch, is also listed as in stock, or disagrees with
  the batch the sale is linked to.
- The cash ledger holds money for the sale (a payment, or a courier completion that credited the sale). The reset does
  not reverse that cash, so it needs reconciliation first.
- A courier consignment exists for a sale that has not left the shop. It must be cancelled with the courier first.
- Advanced accounting holds posted sale journals and cannot reverse them (off, not ready for live posting, or more than
  one journal of the same kind).

Complete delete additionally refuses any sale with a payment, a refund, a cash-ledger entry, a VAT credit note,
warranty records, a warranty claim, a complaint, a download, a courier consignment, or Advanced accounting enabled.

## Advanced accounting verification

After the change, inside the same database transaction, the reset checks the books rather than the return value of the
posting call:

- no sale or sale-cost journal of the order is still posted;
- each original journal has exactly one posted reversal whose totals mirror it;
- the whole ledger balances (total debits equal total credits).

If any check fails, the reset throws and everything rolls back, so a sale is never reported as reversed while its
journal is still posted. The outcome (`verified` or `not_applicable`, with the journal numbers) is written to the audit row.

**Verified end to end:** `tests/Feature/Accounting/AdvancedSaleResetEndToEndTest.php` (3 tests, real cutover through the go-live
harness). A cash-on-delivery sale is journaled by the posting engine when it is confirmed. The reset reverses it through the real
reversal, and the reversal mirrors the original line for line. The books balance, and the cash and inventory checks hold. A second
reset is refused and posts nothing more. A reversal that fails after it posts is rolled back together with the status and the stock.

A paid sale is not covered. The reset refuses any sale with cash in the ledger, because reversing cash is not automated.

## Serial numbers

A sold serial returns to the batch it came from, and only to that batch, once. The origin is read from the batches'
own records (`sn_sold`): a serial must be recorded as sold from exactly one batch, must not be listed as in stock,
and must agree with the sale's linked batch. Nothing is assigned by guesswork. Tests cover the successful path, the
single restore, repeated resets, unknown serials, serials sold from two batches, and a link that disagrees with the serials.

**Sale-side defect: fixed (P0-A).** A completed sale resolves each serial to the one batch that lists it, and a serial that
cannot be resolved refuses the sale. See P0-A below.

## Shipping and courier dependencies

- Shipping rows hold the delivery name, phone and address. No settlement table exists.
- Courier data lives on the order: `courier_type`, `courier_tracking_id`, `courier_sent_at`, `redx_rate`.
- The RedX webhook changes order status through the status service and, on completion, credits the cash ledger
  (`FundHelper::creditSale`, source `sale`). Nothing debits that credit on cancel or return. This is an existing gap in
  normal flows too, and the reset refuses those sales.
- The webhook updates the order without the row lock the reset uses. A webhook that lands during a reset is a race
  that is not closed by the reset.
- Courier remittances (cash collected and handed over) are not modelled. They cannot be reconciled from the data, so
  any sale with courier cash is refused.

## Legacy batch fallback

Normal flows (courier webhooks, returns that are not the reset, and the status service used elsewhere) still restock a
sale that has no recorded batch allocation at the product's **current purchase price**, and log a warning. The reset
path refuses that case. The fallback is kept on purpose: refusing it in normal flows would stop courier returns from
completing. It needs a decision.

Affected transactions: any sale completed or shipped before per-batch allocation was recorded on the order line
(`order_details.batch_ids`), and any sale whose stock was taken by the fallback path (no batches). Those cannot be
reversed safely by the reset. They need manual reconciliation. To find them, run this read-only query on a copy of the
database, not on production by automation:

```sql
SELECT o.id, o.invoice_id, o.order_status, d.id AS line_id, d.product_id, d.qty
FROM orders o JOIN order_details d ON d.order_id = o.id
WHERE o.order_status IN ('confirmed','picking','packing','packed','shipped','out_for_delivery',
                         'delivered','completed','return_requested','return_approved')
  AND d.product_id IS NOT NULL AND d.qty > 0
  AND (d.batch_ids IS NULL OR d.batch_ids = '[]');
```

Average costing charges a sale the product's current purchase price by design. The batch recorded on the line then holds
that price, so the reset restores what the sale was charged, which is consistent with its cost of sales.

## Durable audit

Table `transaction_reset_audits`: no foreign keys, so rows survive deletion of the sale. Insert only: the code creates
rows and never updates or deletes them. Each row is written in the same transaction as the change; a failed write rolls
the change back (tested). It records the action and mode, the sale id and invoice, the previous and final status, the reason,
the actor id and name, the IP, the sale as it was before the change (order, lines, delivery, warranty, buyer), the
accounting outcome, and a reversal summary (restocked batches and where each serial now sits).

Limits: this is a database table, not cryptographic immutability. Anyone with direct database access can change it.
The archive job (`logs:archive-activity`) only prunes the activity log, and a test confirms it leaves these rows alone.
`log_activity()` is still written as a secondary record; it silently ignores its own errors, so it is not the audit.
There is no route that reads the audit; it is reachable only through the database.

## Restore

Not implemented. Its design is a separate, audited action that rebuilds the sale from its audit snapshot, refuses when
stock, payments, cash or journals have changed since the reset, re-allocates stock from current batches, and reissues
VAT rather than reversing it. No one-click rollback is offered.

## Known unresolved risks

1. **RedX callback authentication.** The webhook still has no transport-level authentication: RedX's callback signing or
   token scheme was not found in the code or in the public documentation reached. What is done instead: the status is taken
   from RedX's parcel API with the configured token, never from the payload, and an unverifiable status changes nothing.
   See the RedX section in the hardening results.
2. **Legacy restock at the current purchase price** in normal (non-reset) flows. Kept, because changing it would change
   courier and return behaviour.
3. **Non-serial sale fallback.** When batch tracking fails for a non-serial line, the legacy plain decrement is used and
   logged. Kept on purpose, for the same reason as item 2.
4. **Cash reversal is not automated.** A sale with cash in the ledger, or with a courier consignment, cannot be reset.
5. **Two-connection tests cover** the sales reset, the RedX webhook and the admin cancel. The other admin status paths are
   tested sequentially only.
6. **Legacy data.** Run `php artisan sales:legacy-audit` (read-only) against the live database before any reset. Its findings
   need a person to review them; the command changes nothing.
7. **Accounting timing.** The posting engine books a sale (revenue and receivable) when an order is confirmed. The sales
   reports and the open-dues check recognise a sale only at delivered or completed. For an unpaid sale that is confirmed but
   not delivered, the books and the dues differ. This needs a product decision, and it is not changed here.

## VatCreditNoteTest (fixed)

The five tests in `tests/Feature/Vat/VatCreditNoteTest.php` used to depend on the day they ran: returns are dated now, and the
tests expect October 2026. The test file now freezes the clock to 2026-10-15 in `setUp` and resets it in `tearDown`. The VAT
logic is unchanged.

## P0 fixes (this pass)

### P0-A: sale-side serial integrity

**Root cause.** Completing a sale allocated stock by first-in-first-out, then moved each sold serial only from the batch the
sale was linked to. A serial in any other batch was logged and skipped, and the sale still completed. A failure in stock
allocation fell back to a plain decrement with no batch record, so serial lines could complete without serial ownership.
Nothing rolled back a failed completion: the status was saved before the stock change.

**Fix.**
- `SaleSerialAllocator` resolves every serial to the one batch that lists it in stock, under a row lock on the product's
  batches. It refuses unknown, already-sold, duplicate, multiply-listed and mismatched serials, and a serial count that
  does not match the quantity. Stock is taken from exactly those batches, and each serial is moved to sold in its own batch.
- Serial-tracked lines never fall back to a plain decrement. Non-serial lines keep the legacy path unchanged.
- The whole entering-active block of `handleStatusChange` runs in one transaction.
- The confirm endpoint and the manual status update now lock the order, make the status change and its stock effects in one
  transaction, and return the reason as a 422 on failure.

**Tests.** `tests/Feature/Stock/SaleSerialIntegrityTest.php` (11 tests): the correct serial and batch; a serial in another
batch with no link; a link to the wrong batch; an unknown serial; a duplicate serial; a serial already sold; multiple serials
from multiple batches; a failed line rolling back the whole completion (status, stock, serials, cost fields and stock records);
a retry that moves each serial and each unit exactly once; a count mismatch; and the confirm endpoint refusing and rolling back.

**Legacy callers: done (hardening Phase 3).** The six admin paths now change status and stock in one transaction on a locked,
re-read order: order change, bulk status (one transaction per order), POS create, POS update, return steps, and cancel. A refused
change rolls back the status, the stock and the sale rows, and returns the reason. Tests: `tests/Feature/Orders/LegacyStatusPathsTest.php`
(11 tests) and `tests/Feature/Concurrency/LegacyAdminLockTest.php` (1 test, two database connections). Against the pre-change
controller, 7 of the 11 fail. With the cancel's lock removed, the lock test fails.

### P0-B: courier webhook concurrency

**Root cause.** The RedX webhook and the courier polling command read the order, changed its status and ran the stock
change with no lock and no transaction. A duplicate delivery could restock twice. A webhook could overwrite a reset result,
and a reset could read a stale courier state. The webhook also matched an order by invoice number even when none was sent.

**Fix.**
- `OrderStatusService::changeStatusLocked()` is the one entry point for courier status changes. It locks the order row,
  re-reads it, refuses a change the current status does not allow, and makes the change with its stock, serial and cash
  effects in one transaction. A repeated status is a no-op.
- The RedX webhook and `CheckCourierOrderStatus` use it. The sales reset locks the same order row, so the two cannot
  interleave on the same order.
- The webhook matches by invoice number only when one is sent.
- The reset refuses any sale with a courier consignment, in either mode. The courier's status is only synchronised while a
  parcel is packed or shipped, and nothing confirms what the courier did afterwards. Fail closed.

**Tests.** `tests/Feature/Courier/CourierWebhookConcurrencyTest.php` (5 tests): a duplicate return restocks once; a late
courier message for a reset order is refused and changes nothing; a reset with a courier consignment is refused and reads
the synced state; an unmatched webhook with no invoice number changes no order; and the webhook and the reset both issue
`SELECT ... FOR UPDATE` on the order row.

**Two real connections: covered.** `tests/Feature/Concurrency/ConcurrentSalesResetTest.php` (6 tests) and
`LegacyAdminLockTest.php` (1 test) run each competing action in its own PHP process with its own connection and transaction. Each
test checks that the competitor is really waiting on the order lock (InnoDB `LOCK WAIT`) before it releases it. The fixtures
are committed, so the test tears them down itself, because `DatabaseTruncation` clears them only before the next such test.

**RedX webhook: partly fixed (hardening Phase 1).** The webhook no longer trusts its payload. It requires a tracking number (400),
matches the order by courier type and tracking, and refuses an invoice mismatch (404). It fetches the parcel's status from the
RedX parcel API with the configured token. If that status cannot be read, it fails closed (503) and changes nothing. A status
with no mapping changes nothing. A change is made only through `changeStatusLocked`, and the SMS is sent only when a change was
applied. The route is throttled to 60 requests a minute. **Still open:** the callback itself carries no verified signature, because
RedX's scheme was not found. Anyone can trigger a lookup, which is rate-limited, but a forged message cannot move an order. This
needs RedX's documented authentication scheme before release.

## Hardening pass: results

Run on 2026-11-04 against `ecommerce3_test` only. No production database was read or written, and no migration was run there.

| Phase | What changed | Tests |
|---|---|---|
| 1. RedX webhook | Status comes from the RedX parcel API with the configured token, never from the payload. Fails closed (503) when it cannot be read. Invoice mismatch returns 404. Throttled to 60 a minute. | `RedXWebhookTest` (7), `CourierWebhookConcurrencyTest` (5) |
| 2. Concurrency | Competing actions run in separate PHP processes, each with its own connection. The test checks the competitor is really waiting on the order lock (InnoDB `LOCK WAIT`) before releasing it. | `ConcurrentSalesResetTest` (6), `LegacyAdminLockTest` (1) |
| 3. Legacy admin paths | Order change, bulk status (one transaction per order), POS create, POS update, return steps and cancel now change status and stock in one transaction on a locked, re-read order. Duplicate serials on a POS sale are refused, not a 500. | `LegacyStatusPathsTest` (11) |
| 4. Legacy audit and Advanced accounting | `sales:legacy-audit` is read-only, lists each legacy fault, and writes nothing (checksum test). Advanced accounting is tested end to end on a real posted sale journal. The VAT credit-note test clock is frozen. | `SalesLegacyAuditTest` (2), `AdvancedSaleResetEndToEndTest` (3), `VatCreditNoteTest` (5) |

**Mutation checks.** Against the pre-change controller, 7 of the 11 `LegacyStatusPathsTest` tests fail (the injected-failure cases).
With the cancel's `lockForUpdate()` removed, `LegacyAdminLockTest` fails: the cancel returns 200 on a delivered sale. Both controllers were restored byte-for-byte afterwards.

**Full suite:** 731 passed, 0 failed (3365 assertions).

### RedX, status

The callback is still not authenticated at the transport level. RedX's signing or token scheme was not found in the code or in
the public documentation reached. A forged message cannot move an order, because the status is read from RedX's parcel API.
Someone can still trigger lookups, which are rate-limited. This stays a release blocker until RedX's documented scheme is checked.

### Remaining blockers

1. RedX callback authentication (above).
2. Cash reversal is not automated. A sale with cash in the ledger, or with a courier consignment, cannot be reset.
3. Accounting timing: the engine books a sale when it is confirmed. The sales reports and the open-dues check recognise it only
   at delivered or completed. A confirmed but undelivered unpaid sale shows different receivable and dues figures. Needs a product decision.
4. Legacy restock at the current purchase price in normal flows, and the non-serial batch fallback. Both kept on purpose.
5. The legacy audit (`php artisan sales:legacy-audit`) must be run on the live database before any reset, and its findings reviewed by a person.
