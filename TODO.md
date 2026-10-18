# TODO — VAT, accounting and go-live

Status key: `[ ]` not started · `[~]` in progress · `[x]` done · `[!]` blocked on a decision or a person

## A. Decisions (you)

- [!] A1. Is the business VAT-registered? (blocks all VAT rules)
- [!] A2. Rate per product category (standard / reduced / zero / exempt)
- [!] A3. Do selling prices include VAT?
- [!] A4. Do you sell to VAT-registered buyers? (decides whether 6.3 challans are needed)
- [!] A5. 9.1 filing frequency: monthly or quarterly?
- [!] A6. Supplementary duty or other levies applicable?
- [!] A7. Supplier return: do goods leave our stock? (the code currently adds them)
- [!] A8. Purchase return: reduce the supplier due, or refund cash?
- [!] A9. New shops with no opening balances: allow a "start empty" option?
- [!] A10. Build customer and supplier store credit?

## B. Checks by a person

- [!] B1. Practitioner reviews the 9.1 draft mapping (`app/Services/Vat/Mushak91Draft.php`)
- [!] B2. Bengali reader checks official headings on 6.1, 6.2 and 9.1
- [!] B3. Practitioner confirms the rate categories and the 9.1 line assumptions

## C. VAT build

- [x] C1. Data model: VAT columns (nullable), no behaviour change
- [x] C2. `VatCalculator` behind two switches
- [x] C3. Line snapshots at sale and purchase creation
- [x] C4. Credit notes for returns and cancellations, with numbering
- [x] C5. Settings page (`admin/vat/settings`)
- [x] C6. Product VAT rate, category and HS code on the forms
- [x] C7. Registers, return summary and CSV exports (working columns)
- [x] C8. Official forms downloaded and catalogued (`bd-vat-mushak/official-forms/FORMS.md`)
- [x] C9. Mushak 9.1 draft by official line numbers (not for filing)
- [ ] C10. Mushak 9.1 final: closing balance (lines 36–51), supplementary duty, printable layout — blocked on A1, A2, A5, B1
- [ ] C11. Mushak 6.1 and 6.2 in full official layout — blocked on B2
- [ ] C12. Mushak 6.3 challans — blocked on A4
- [ ] C13. Mushak 6.10 (challans above Tk 2 lakh) — build only if the business issues such challans
- [x] C14. Link VAT pages from the settings menu (admin sidebar, `setting-edit`)
- [x] C15. Warn at sale time when a product has no VAT rate while VAT is on (POS, sale still goes through)

## D. Accounting (Advanced layer)

- [x] D1. Expenses, money-in, withdrawals gated on readiness (no double count)
- [x] D2. Opening derived from records; refuses when trading has moved on
- [x] D3. Sales, COGS, payments, refunds, returns, stock adjustments, purchase returns, loans, fixed assets, depreciation, salary, bonus, warranty income journaled
- [x] D4. Coverage test per event (`AccountingCoverageTest`)
- [ ] D5. Journal damage write-offs
- [x] D6. Warranty supplier-charge and customer-charge journals covered by a books test
- [x] D7. Trial balance checked after every accounting event (balanced, zero difference)
- [ ] D8. Journal supplier return — blocked on A7
- [ ] D9. Purchase return: supplier due rule — blocked on A8
- [ ] D10. Variant stock adjustments still write stock directly (not journaled; variant copy only)
- [ ] D11. Opening for a brand-new shop — blocked on A9

## E. Stock and sales

- [x] E1. Stock adjustment quantity bug fixed (+2 was +10)
- [x] E2. Stock adjustment variant id bug fixed (admin id was passed as variant id)
- [ ] E3. Variant stock adjustment writes `product_variant_prices.stock` directly — route through the stock service
- [ ] E4. Partial sales returns (the app only supports whole-order returns)

## F. Store credit — blocked on A10

- [ ] F1. Customer store-credit ledger
- [ ] F2. Use store credit as a payment method (no fund row)
- [ ] F3. Supplier credit and its use against a purchase

## G. Before go-live

- [ ] G1. Split the uncommitted `lara` work into commits (about 90 pre-existing files)
- [ ] G2. Commit `bd-double-entry` changes (chart roles, source types)
- [ ] G3. Run the full suite on a copy of the real data
- [ ] G4. Reconcile the real data: till, stock, receivables, payables, VAT
- [ ] G5. Re-check NBR forms before each filing period (circulars change them)
