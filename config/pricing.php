<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batch-Wise Pricing Engine
    |--------------------------------------------------------------------------
    | Master switch for the batch-anchored pricing model. When OFF the system
    | keeps the legacy resolution (products.new_price → variant price) so the
    | upgrade can be shipped and rolled back without breaking the storefront.
    |
    | Env: BATCH_WISE_PRICING (bool) — default false
    */
    'batch_wise' => (bool) env('BATCH_WISE_PRICING', false),

    /*
    |--------------------------------------------------------------------------
    | Default Website Batch Selection (FIFO)
    |--------------------------------------------------------------------------
    | 'oldest'  → oldest website-enabled batch with stock (first-in, first-out)
    | 'newest'  → newest batch with stock (last-in, first-out)
    */
    'website_batch_default' => env('PRICING_WEBSITE_BATCH_DEFAULT', 'oldest'),

    /*
    |--------------------------------------------------------------------------
    | Auto-advance
    |--------------------------------------------------------------------------
    | When the active website batch sells out, automatically activate the next
    | FIFO batch with stock. Can be overridden per product from the UI.
    */
    'auto_advance_default' => (bool) env('PRICING_AUTO_ADVANCE_DEFAULT', true),

    /*
    |--------------------------------------------------------------------------
    | Cached catalog columns
    |--------------------------------------------------------------------------
    | When true, products.website_price / products.website_stock are maintained
    | (on batch activation / stock change) so catalog listings & filters stay fast
    | without per-row PricingService calls.
    */
    'cache_website_price' => (bool) env('PRICING_CACHE_WEBSITE_PRICE', true),

    /*
    |--------------------------------------------------------------------------
    | Multi-batch order pricing
    |--------------------------------------------------------------------------
    | 'active_batch' → charge the active batch price for the whole product line
    |                  (matches "publicly show only one batch")
    | 'per_batch'    → itemize each FIFO batch portion at its own price
    */
    'multi_batch_pricing' => env('PRICING_MULTI_BATCH_PRICING', 'active_batch'),
];
