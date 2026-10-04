# Payment Accounting Foundation — Phase 2B-1

Date: 2026-10-04

This phase hardens payment traceability and reconciliation. It does not add a
payment purge handler, route, button, or destructive operation.

## Customer payments

`PaymentCollectionService` is the single new-payment primitive. In one database
transaction it creates `OrderPayment`, derives order paid/due/status from the
payment-history ledger, refreshes the flat `payments` state row, and creates an
exact `customer_payment` fund row linked in both directions. The nullable link
is intentionally not backfilled; historical `source=sale` rows remain legacy
order aggregates and are reported separately.

`CustomerDueReconciliationService` derives `paid = SUM(order_payments.amount)`,
`due = max(order.amount - paid, 0)`, and pending/partial/paid status.

## Supplier payments

`SupplierPaymentReconciliationService` owns the deterministic fund/payment pair
for new paths and derives purchase and supplier due values from purchase and
payment history. `opening_balance` is not fabricated into history; non-zero
opening-AP semantics remain a future readiness dependency.

## Legacy-safe reporting

When a canonical customer payment exists for an order, legacy sale fund rows for
that order are excluded from realizable cash aggregation so actual payment rows
cannot be counted twice. Orders with only historical sale rows retain the
existing uncollected-COD rule. No historical row is rewritten.

The Phase 2B readiness service remains globally blocked. Refunds, returns,
opening balances, advanced journal dependencies, and downstream effects still
require a separate proven purge design.
