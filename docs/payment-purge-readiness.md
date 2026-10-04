# Payment Purge Readiness — Phase 2B-1 foundation state

Date: 2026-10-04
Decision: **NOT SAFE FOR PHASE 2B IMPLEMENTATION**

This remains a read-only audit. No customer-payment or supplier-payment purge
handler or executable route was added. Phase 2B-1 hardens new payment tracing
and reconciliation only; it does not authorize purge.

## Customer Payment

### Canonical source

The canonical operational source is `OrderPayment`.

`PaymentCollectionService::collect()` creates one row with:

- `order_id`: required order relationship
- `customer_id`: copied from the order
- `amount`: capped against the order's remaining lifetime collection
- `payment_method`
- `trx_note`: gateway transaction/reference or note
- `created_by`
- `created_at`: payment event date

Payment creation is used by POS, order-admin payment flows, customer due
collection, and payment gateway callbacks through the shared collection service.

### Due authority

`OrderPayment` history is authoritative for paid amount and due calculation.
`Order::recalculatePaymentTotals()` sums remaining `order_payments.amount`,
then writes the denormalized `orders.paid_amount`, `orders.due_amount`, and
`orders.payment_status`.

Therefore a future handler could recalculate a single order's due/status after
removing a payment. The flat `payments` row is also refreshed by the collection
service and would need to be rebuilt from the remaining payment history.

This proves the order-side due calculation, but does not prove a complete payment
purge because the Lite fund representation is not payment-specific.

### Fund state after Phase 2B-1

Historical Lite fund credits are stored as:

- `source = sale`
- `source_id = order_id`
- `direction = in`
- amount capped to the order's aggregate credited amount

New payments are now stored as one `customer_payment` FundTransaction per
OrderPayment, with `OrderPayment.fund_transaction_id` pointing back to it.
Historical rows remain order-level sale aggregates. With legacy payments of
3,000 and 2,000 against a 10,000 order, the fund still has order-level sale
credit, not independently addressable payment credits.

Consequences:

- No guaranteed `source_id = OrderPayment.id` linkage exists.
- Deleting Payment #1 cannot safely delete exactly one FundTransaction.
- Deleting the aggregate sale fund row would incorrectly remove Payment #2's cash.
- Historical rows cannot be repaired by amount/date inference.

This remains the primary hard blocker for historical payments. New exact links
remove only this ambiguity for newly collected payments; the global purge gate
and downstream dependency blockers remain.

### Advanced journal

The Advanced journal source is:

- `SourceType::CUSTOMER_PAYMENT`
- `source_id = OrderPayment.id`
- posting key `customer-payment:{OrderPayment.id}`
- Dr default fund/cash
- Cr Accounts Receivable
- customer party trace when a customer ID exists
- actor from `OrderPayment.created_by`
- date from `OrderPayment.created_at`

The administrative journal bridge can identify this source by
`source_type + source_id`, and blocks ambiguous or reversed journal chains.
That alone is insufficient because Lite fund and order-side consequences must
also reconcile.

### Other dependencies and blockers

- Any refund on the order blocks payment purge:
  `DEPENDENT_REFUND_EXISTS`. Refunds are order-linked, not payment-linked,
  so a refund cannot safely be assigned to one payment by inference.
- Cancelled, returned, return-approved, or closed orders block:
  `ORDER_CANCELLED_OR_RETURNED`.
- Missing order/customer or missing fund representation blocks.
- Advanced enabled with no unique CUSTOMER_PAYMENT journal blocks.
- Reversed/ambiguous journal identity blocks.
- Opening-AR settlement requires explicit cutover proof before future enablement.
- Payment purge remains globally blocked in Phase 2B-0.

### Multiple payments

For Order = 10,000, Payment #1 = 3,000, Payment #2 = 2,000:

- Current due = 5,000.
- Order-side recalculation could produce due = 8,000 after removing #1.
- Revenue, COGS, and inventory would remain unchanged.
- Advanced AR could increase by 3,000 if the payment journal were removed.
- Lite fund cannot safely decrease by exactly 3,000 because the current fund row
  is order-aggregate. Therefore the complete purge is unsafe.

### Full payment

For Order = 10,000 and one Payment = 10,000:

- Due is stored but derived from OrderPayment history.
- Payment status is stored but recalculated by `recalculatePaymentTotals()`.
- Removing the payment would mathematically restore due = 10,000 and pending status.
- The aggregate fund row and any order-level sale credit still require a safe,
  payment-specific reconciliation path.

### Opening AR

Opening balances build customer AR from operational order due values and create
party-level opening lines. A payment journal may reduce the same customer's AR,
but payment purge must prove:

1. the order/payment date is in the live cutover regime;
2. the payment journal's party trace is the same customer;
3. the opening AR line remains the correct source balance;
4. Lite due and Advanced AR reconcile after recalculation.

This has not been proven for execution, so opening-AR-related payments remain
blocked.

## Supplier Payment

### Canonical source

The canonical source is `SupplierPayment`:

- `supplier_id`
- nullable `purchase_id`
- `amount`
- `payment_date`: business date
- `method`
- `note`
- `fund_transaction_id`
- `created_by`

Current creation paths in PurchaseController create the FundTransaction first,
create SupplierPayment, then update:

- `FundTransaction.source = supplier_payment`
- `FundTransaction.source_id = SupplierPayment.id`
- `SupplierPayment.fund_transaction_id = FundTransaction.id`

The linkage is guaranteed in those current transactional paths. Historical rows
with null/mismatched linkage remain blocked.

### Supplier due authority

Supplier payment flows mutate persisted values:

- `Purchase.paid_amount`
- `Purchase.due_amount`
- `Supplier.current_due`

Supplier Lite ledgers read purchases and their payments, but the operational
current-due columns remain persisted state used by the application. A safe purge
must recompute purchase paid/due from authoritative purchase payment history and
reconcile Supplier.current_due from all purchases/payments. It must not simply
add the payment amount back to current_due without proving there are no later
edits, returns, or other inconsistencies.

### Advanced journal

The Advanced journal is:

- `SourceType::SUPPLIER_PAYMENT`
- `source_id = SupplierPayment.id`
- posting key `supplier-payment:{SupplierPayment.id}`
- Dr Accounts Payable
- Cr default fund/cash
- supplier party trace when available
- date from `SupplierPayment.payment_date`

Unique posted journal identity is detectable. Reversal or ambiguity remains a
blocker.

### Other dependencies and blockers

- Missing or mismatched FundTransaction linkage blocks.
- Missing Purchase or Supplier blocks.
- Any SupplierReturn for the purchase blocks:
  `DEPENDENT_SUPPLIER_RETURN_EXISTS`. Supplier returns currently have known
  operational/AP limitations.
- Persisted purchase/supplier due reconciliation is not yet a proven purge
  algorithm: `SUPPLIER_DUE_RECOMPUTATION_REQUIRED`.
- Opening-AP settlement requires cutover and party-level proof.
- Missing, reversed, or ambiguous Advanced journal blocks when Advanced is on.
- Phase 2B-0 globally blocks execution.

## Readiness matrix

| Event | Lite source | Fund linkage | Advanced identity | Current readiness |
|---|---|---|---|---|
| Customer Payment | OrderPayment | Order-level sale aggregate, not payment-specific | Unique CUSTOMER_PAYMENT journal | BLOCKED |
| Supplier Payment | SupplierPayment | One-to-one on current paths; historical gaps | Unique SUPPLIER_PAYMENT journal | BLOCKED |

## Reconciliation proof required before Phase 2B

### Customer payment

For the 10,000 order / 3,000 + 2,000 payments example, a future safe handler
must prove after purging #1:

- remaining payments = 2,000
- order paid = 2,000
- order due = 8,000
- payment status = partial/pending according to application rule
- exactly 3,000 removed from Lite fund
- exactly 3,000 restored to Advanced AR
- revenue unchanged
- COGS unchanged
- inventory unchanged
- trial balance balanced
- no refund/return/cancellation conflict
- no orphan journal lines

The current order-aggregate fund design cannot satisfy the exact fund step.

### Supplier payment

For a 10,000 purchase with a 4,000 payment, a future safe handler must prove:

- purchase paid = 0 and due = 10,000 after purge
- supplier current_due is recomputed from authoritative history
- exactly 4,000 is restored to Lite fund
- exactly 4,000 is restored to Advanced AP
- purchase recognition and inventory unchanged
- P&L unchanged
- trial balance balanced
- no supplier-return conflict
- no orphan journal lines

## Historical-data blockers

A payment is blocked if any of these are found:

- missing `fund_transaction_id`
- fund source/source ID mismatch
- customer fund row is only an order aggregate
- missing order/purchase/party
- missing or ambiguous Advanced journal identity
- reversed journal relationship
- refund or return dependency
- cancelled/returned/closed order
- opening AR/AP relationship not proven
- persisted due fields cannot be recomputed authoritatively

## Readiness implementation

Added read-only `PaymentPurgeReadinessService`. It reports dependency graphs
and blockers for customer and supplier payments. It does not authorize or
delete either payment type. The Transaction Control Center remains without an
active payment purge button.

## Final decision

**NOT SAFE FOR PHASE 2B IMPLEMENTATION**

Exact prerequisites:

1. Introduce or prove a payment-specific Lite fund representation for customer
   payments, with safe handling of historical aggregate rows.
2. Define and test customer due/payment-summary reconciliation after deleting
   one payment, including full/multiple payments.
3. Define refund, return, cancellation, and opening-AR blockers and tests.
4. Define authoritative supplier purchase/supplier due recomputation.
5. Resolve supplier-return interaction with AP and inventory.
6. Add complete Advanced ON/OFF/package-absent payment dependency tests.
7. Add atomic payment handlers only after the above proofs pass.

No payment purge handler was implemented.
