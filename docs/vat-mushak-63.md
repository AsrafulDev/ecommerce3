# Mushak 6.3 (tax challan): official form, data model, rules and open questions

Status: implemented as a draft. **Not approved for filing or for handing over to buyers.** A Bengali-literate reviewer must
check the printed copy against the official NBR form, and a VAT practitioner must answer the questions in section 6.

## 1. Official source

- File: `bd-vat-mushak/official-forms/Mushak_6.3.pdf` (one A4 page, Microsoft Word 2010, dated 29 June 2019 in its metadata).
- Listed in `bd-vat-mushak/official-forms/FORMS.md` as "Tax challan (Kor chalanpatra)".
- The PDF's text layer is garbled (the Bengali glyphs do not extract), so the fields below were transcribed from a rendered
  image at 220 dpi, not from extracted text. Every label must be checked by a Bengali-literate reviewer.
- The NBR emblem at the top left is an image in the form. It is not reproduced here: no artwork was available, and it was not invented.

## 2. Fields on the form

Order and wording follow the form. The English meaning is ours and needs confirming.

### Heading

| Printed | Meaning (ours) |
|---|---|
| গণপ্রজাতন্ত্রী বাংলাদেশ সরকার / জাতীয় রাজস্ব বোর্ড | Government of Bangladesh / National Board of Revenue |
| মূসক-৬.৩ | Form number (Mushak-6.3) |
| কর চালানপত্র | Tax challan |
| [ বিধি ৪০ এর উপ-বিধি (১) এর দফা (গ) ও দফা (চ) দ্রষ্টব্য ] | See rule 40, sub-rule (1), clauses (গ) and (চ). **See open question Q1.** |

### Seller and buyer block

| # | Printed label | Meaning (ours) | Form field kind |
|---|---|---|---|
| 1 | নিবন্ধিত ব্যক্তির নাম: | Registered person's name (seller) | text |
| 2 | নিবন্ধিত ব্যক্তির বিআইএন: | Registered person's BIN | text |
| 3 | চালানপত্র ইস্যুর ঠিকানা: | Address where the challan is issued | text |
| 4 | চালানপত্র নম্বর: | Challan number | text |
| 5 | ইস্যুর তারিখ: | Issue date | date |
| 6 | ইস্যুর সময়: | Issue time | time |
| 7 | ক্রেতার নাম: | Buyer's name | text |
| 8 | ক্রেতার বিআইএন: | Buyer's BIN | text |
| 9 | সরবরাহের গন্তব্যস্থল: | Destination of supply | text |

### Line table (10 columns, then a total row)

| # | Printed heading | Meaning (ours) | Total row |
|---|---|---|---|
| 1 | ক্রমিক | Serial number | – |
| 2 | পণ্য বা সেবার বর্ণনা (প্রযোজ্য ক্ষেত্রে ব্র্যান্ড নামসহ) | Description of goods or services, with brand name where applicable | – |
| 3 | সরবরাহের একক | Unit of supply | – |
| 4 | পরিমাণ | Quantity | – |
| 5 | একক মূল্য¹ (টাকায়) | Unit value in taka, ¹ see footnote | – |
| 6 | মোট মূল্য¹ (টাকায়) | Total value in taka, ¹ see footnote | yes |
| 7 | সম্পূরক শুল্কের পরিমাণ (টাকায়) | Supplementary duty amount in taka | yes |
| 8 | মূল্য সংযোজন করের হার/সুনির্দিষ্ট কর | VAT rate, or specific tax | – |
| 9 | মূল্য সংযোজন কর/সুনির্দিষ্ট কর এর পরিমান (টাকায়) | VAT or specific tax amount in taka | yes |
| 10 | সকল প্রকার শুল্ক ও করসহ মূল্য | Value including all duties and taxes | yes |

The total row is labelled সর্বমোট (grand total). It spans columns 1 to 5.

### Signature block

| Printed | Meaning (ours) |
|---|---|
| প্রতিষ্ঠান কর্তৃপক্ষের দায়িত্বপ্রাপ্ত ব্যক্তির নাম: | Name of the person responsible for the establishment |
| পদবী : | Designation |
| স্বাক্ষর: | Signature |
| সীল: | Seal |

These are left blank on the printed copy, for hand completion.

### Footnotes

- ¹ "সকল প্রকার কর ব্যতীত মূল্য": value excluding all taxes. This applies to columns 5 and 6.
- \* "উৎসে কর্তনযোগ্য সরবরাহের ক্ষেত্রে ফরমটি সমন্বিত কর চালানপত্র ও উৎসে কর কর্তন সনদপত্র হিসেবে বিবেচিত হইবে এবং উহা উৎসে কর কর্তনযোগ্য সরবরাহের ক্ষেত্রে প্রযোজ্য হবে।": where the supply is subject to tax deduction at source, this form counts as the integrated challan and TDS certificate, and applies to such supplies. The app does not handle TDS.

### What the form does not have

No challan series, payment reference, void or cancellation field, delivery-challan reference, or signature date. None of these
has been added to the printed copy.

## 3. Mapping from the form to our data

| Form field | Source in the app | Notes |
|---|---|---|
| Seller name, BIN, issue address | `general_settings.vat_business_name`, `vat_bin`, `vat_business_address` | Copied into the challan at issue. Address assumption: Q6. |
| Challan number | `vat_challans.challan_no` | Format `CH-YYYY-NNNNNN` is ours. See Q4. |
| Issue date, issue time | `vat_challans.issued_at`, `issue_date`, `issue_time` | Server time at issue. Time format: Q5. |
| Buyer name | `shippings.name`, else `customers.name` | Copied at issue. |
| Buyer BIN | `orders.buyer_bin` | Not captured by the app before this change. Now entered per sale with validation. |
| Destination of supply | `shippings.address` | Assumption: the delivery address. Q14. |
| Description | `order_details.product_name` and `brands.name` | Brand shown as "name (brand)" where one is set. |
| Unit of supply | `products.pro_unit` | Left blank when the product has no unit. |
| Quantity | `order_details.qty` | |
| Unit value | `order_details.taxable_value` ÷ `qty` | Stored to 4 decimals, printed to 2. Basis: Q7. |
| Total value (col 6) | `order_details.taxable_value` | Taxable value, excluding VAT. |
| SD (col 7) | not tracked | Left blank, not printed as zero. Q9. |
| VAT rate (col 8) | `order_details.vat_rate` | Snapshot at sale time. |
| VAT amount (col 9) | `order_details.vat_amount` | Snapshot at sale time. Nothing is recomputed. |
| Total incl. tax (col 10) | taxable value + VAT | |

## 4. Data model

Migration: `database/migrations/2026_11_05_000001_create_vat_challans_tables.php`. Models: `app/Models/VatChallan*.php`,
`VatDocumentSequence`.

- **`vat_challans`**: one row per challan. Carries the seller, buyer and totals as a snapshot taken at issue. Only `status`,
  `issued_order_id`, and the void fields change afterwards. `challan_no` is unique. `issued_order_id` is set only while the
  challan is issued, with a unique index, so the database refuses a second issued challan for one sale. Voiding clears it.
  `pdf_path` and `pdf_sha256` record the stored printed copy.
- **`vat_challan_lines`**: one row per sale line, with `serial`, `quantity`, `unit_value`, `line_value`, `sd_amount` (null
  when not applicable), `vat_rate`, `vat_amount` and `line_total`.
- **`vat_challan_events`**: insert-only audit trail. The model refuses update and delete. Events: `issued`, `voided`,
  `pdf_downloaded`, `buyer_bin_set`.
- **`vat_document_sequences`**: the last number per series and year. Read and advanced under a row lock, inside the issuing transaction.

No VAT is recomputed for the challan. The lines copy the VAT snapshot that `order_details` already holds, so the challan,
the 6.2 register and the 9.1 figures use the same numbers.

## 5. Rules applied before issue

Implemented in `app/Services/Vat/Challan/ChallanEligibility.php`. A blocker stops the issue. A warning does not.

**Blockers**
1. VAT is switched off.
2. The sale is not delivered or completed (cancelled, returned, pending and every other status are refused).
3. A challan is already issued for the sale.
4. A VAT credit note already reverses the sale.
5. The seller's BIN is missing or not 12 digits.
6. The seller's business name or address is missing.
7. The buyer's BIN is missing or not 12 digits.
8. The buyer's name or delivery address is missing.
9. The sale has no lines.
10. A line has no recorded taxable value, VAT amount or VAT rate.

**Warnings**
- Delivery charges are not lines on the challan (Q8).
- An order-level discount is not shown on the lines (Q8).
- The category-to-line mapping for the 9.1 return is not approved. This does not change the challan layout.

**12-digit BIN.** Taken from `bd-vat-mushak/PLAN.md` section 2 ("validated as a 12-digit BIN"). Whether a check digit or a
registration lookup is also needed is Q3.

**No automatic issue.** A challan is issued only when a user chooses the sale: one at a time, or from a selection. Nothing
issues on sale creation or on delivery.

## 6. Open questions for the practitioner

Each question is also shown on the challans page or in the code comment where it applies.

1. **Clause reference.** The form prints clauses (গ) and (চ) of rule 40(1). `FORMS.md` says "(ga) and (gha)". The second letter
   printed is চ (cha), not ঘ (gha). Which clauses govern this challan?
2. **When a challan is required.** Is one needed for every sale to a VAT-registered buyer, or only for some goods, values or
   sale types? Is the time of supply the delivery, or the invoice? The app uses delivered or completed.
3. **Buyer identifier.** Is a 12-digit BIN enough, or must the check digit or the registration status also be verified? Is
   a buyer without a BIN handled by a different form?
4. **Challan number.** Is `CH-YYYY-NNNNNN` acceptable, or does NBR set a series, prefix, or reset rule (per year, per branch)?
5. **Issue time format.** The PDF prints 12-hour time ("02:25 PM"). Should it be 24-hour?
6. **Issuing address.** Is "চালানপত্র ইস্যুর ঠিকানা" the business premises, or the place where the challan is issued? The app uses
   the business address.
7. **Unit value basis.** Is the unit value the taxable value divided by quantity? How should a non-exact division be rounded,
   and should the unit value be shown at all?
8. **Delivery charges and discounts.** Do delivery charges carry VAT and need a line? Should line values be shown after
   the order discount?
9. **Supplementary duty and specific tax.** When the product has neither, should columns 7 and 8 stay blank (the app's
   choice) or show zero? Does this seller have any SD or specific-tax products?
10. **Prices including VAT.** The repo's A3 decision is unresolved (`vat_prices_include_vat`). It changes what the taxable
    value is, so it must be settled before any challan is printed.
11. **Void, return and cancellation.** The form has no void field. The app keeps a voided challan on record and allows a
    new one under a new number. The NBR procedure for a sale returned after its challan was issued (credit note only, or a
    replacement challan) is not in the repo. Issued challans are left as they are.
12. **Responsible person and signature.** The name, designation, signature and seal are left blank for hand completion.
    Should the name and designation be printed from settings?
13. **Emblem.** The official form carries the NBR emblem. The printed copy does not. Must it?
14. **Destination of supply.** Is the delivery address the right destination for goods? Should a pickup or a service
    have a different destination?
15. **Retention.** How long must the stored printed copy and the audit trail be kept, and in what form?
16. **Printed copy as the record.** Is the stored PDF (with its SHA-256) the copy NBR requires, or only a working copy?
17. **Bengali text.** The labels were transcribed from a rendered image. Each one must be checked character by character.

## 7. Implementation summary

| Area | Where |
|---|---|
| Eligibility and blockers | `app/Services/Vat/Challan/ChallanEligibility.php`, `ChallanRules.php` |
| Issue, void (transactional, locked) | `app/Services/Vat/Challan/ChallanIssuer.php` |
| Numbering (locked sequence row) | `app/Services/Vat/Challan/ChallanNumberer.php` |
| Buyer BIN capture (audited) | `app/Services/Vat/Challan/ChallanBuyer.php` |
| PDF and stored-copy check | `app/Services/Vat/Challan/ChallanPdf.php`, `resources/views/backEnd/vat/challans/pdf.blade.php` |
| Register and eligibility list | `app/Services/Vat/Challan/ChallanRegister.php` |
| Pages and actions | `app/Http/Controllers/Admin/VatChallanController.php`, `resources/views/backEnd/vat/challans/index.blade.php`, `show.blade.php` |
| Routes | `routes/web.php`, the `vat/challans` group |
| Reconciliation | `VatReportService::reconciliation()`: challan VAT must equal the VAT on the same sales |
| Link from VAT reports | `resources/views/backEnd/vat/reports.blade.php` |
| CSV register | `admin.vat.challans.export` (issued challans, with the void reason) |

**Access.** Viewing, the printed copy and the CSV need `setting-list` or `setting-edit`, as the VAT reports do. Issuing,
voiding and setting a buyer BIN need `setting-edit`. There is no separate permission for challans yet.

**Concurrency.** An issue locks the sale row, checks eligibility inside the lock, then takes the next number under a lock on
the sequence row. The sequence row is created in its own statement first. Creating it inside the issuing transaction
deadlocked under two concurrent issues: this was found by the concurrency test and fixed.

## 8. Tests

| File | Covers |
|---|---|
| `tests/Feature/Vat/Challan/ChallanEligibilityTest.php` | Each blocker and warning; BIN shapes; the credit-note and already-issued refusals |
| `tests/Feature/Vat/Challan/ChallanIssueTest.php` | Number, lines, totals, brand, stored-PDF hash, printed fields, duplicates (service and database), no-gap numbering, void and re-issue, BIN audit, returned sales, pages, bulk issue, access control, reconciliation, report link |
| `tests/Feature/Vat/Challan/ChallanConcurrencyTest.php` | Two processes on one sale (one challan, one refusal, both proven waiting on the lock), and three processes on three sales (distinct sequential numbers) |

Mutation check: with the sale lock removed from the issuer, the same-sale concurrency test fails.

## 9. Readiness

| Item | Status |
|---|---|
| Form fields extracted from the official PDF | Done, from the rendered image. Needs a Bengali-literate reviewer. |
| Data model | Done. Schema is additive. Not run on any production database. |
| Rules | Implemented from the form and the repo's plans. Need practitioner sign-off (section 6). |
| Printed layout | Matches the form's fields and order. The emblem is missing. The reviewer must compare it with the official PDF. |
| Tests | 46 challan test cases (eligibility 20, issue 24, concurrency 2). Full suite: 777 passed, 0 failed. |
| Production readiness | **Not ready.** Blocked on Q1–Q4, Q10 and Q11 at least, and on the reviewer's sign-off of the printed copy. |
