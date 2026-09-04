<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batch-Wise Pricing Engine
    |--------------------------------------------------------------------------
    | The system is batch-wise by design (no toggle): prices, wholesale and
    | warranty are batch-scoped, and a product without a sellable batch shows as
    | out of stock. The legacy products.new_price resolution is only a mirror.
    */
    'batch_wise' => true,

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
