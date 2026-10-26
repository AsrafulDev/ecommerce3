<?php

/*
|--------------------------------------------------------------------------
| VAT (Mushak) configuration — decisions and provisional mappings
|--------------------------------------------------------------------------
|
| Nothing here is a legal determination. Every value that depends on a business
| decision or a practitioner's review is null or false until it is approved.
| A filing-ready return is never produced while any of these are unresolved.
|
| A1 registration, A3 price basis and the VAT rates per product are stored in
| general settings and products. They are not defaulted here.
*/

return [

    /*
    | A5 — filing frequency for the 9.1 return: 'monthly' or 'quarterly'.
    | Null = not decided. Sources disagree; confirm with NBR or a practitioner.
    */
    /*
    | Customs tariff used for HS code search on products. JSON array of
    | {code, description, cd, sd, vat, ait, rd, at}. Built from the official
    | Bangladesh National Board of Revenue tariff PDF by scripts/build-hs-tariff.sh.
    | Missing file = search returns nothing; nothing is guessed.
    */
    'hs_tariff_path' => storage_path('app/vat/hs-tariff.json'),

    'filing_frequency' => null,

    /*
    | Sign-off: set to true ONLY after a qualified Bangladesh VAT practitioner has
    | reviewed the mappings below and that review is recorded in
    | bd-vat-mushak/REVIEW-PACKET.md. Until then every return is a draft.
    */
    'mapping_approved' => false,

    /*
    | Product VAT category → Mushak 9.1 line. PROVISIONAL: assumptions, see
    | REVIEW-PACKET.md section 3 for the evidence and the open questions for each.
    | An unknown category is never placed on a line; it is reported as unmapped.
    */
    'sales_lines' => [
        'zero'     => 1,   // শূন্যহার — assumed domestic zero-rated; export vs local unconfirmed
        'exempt'   => 3,   // অব্যাহতিপ্রাপ্ত পণ্য/সেবা
        'standard' => 4,   // আদর্শ হারের পণ্য/সেবা
        'reduced'  => 7,   // আদর্শ হার ব্যতীত অন্যান্য হারের পণ্য/সেবা
    ],

    /*
    | Purchases: local supply assumed. Imports (lines 11, 13, 15, 17) are not tracked.
    */
    'purchase_lines' => [
        'zero'     => 10,  // শূন্যহার — স্থানীয় ক্রয় (assumed local)
        'exempt'   => 12,  // অব্যাহতিপ্রাপ্ত — স্থানীয় ক্রয় (assumed local)
        'standard' => 14,  // আদর্শ হারের — স্থানীয় ক্রয় (assumed local)
        'reduced'  => 16,  // আদর্শ হার ব্যতীত অন্যান্য — স্থানীয় ক্রয় (assumed local)
    ],
];
