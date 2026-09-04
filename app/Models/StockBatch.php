<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'variant_price_id', 'purchase_id', 'supplier_id',
        'batch_no', 'quantity', 'remaining_qty', 'sn_stock', 'sn_sold',
        'unit_cost', 'total_cost', 'selling_price', 'custom_field',
        'mfg_date', 'exp_date', 'type', 'reference_type', 'reference_id', 'reference_no',
        // batch-wise pricing engine
        'mrp', 'wholesale_price',
        'is_active_for_website', 'pos_enabled', 'auto_advance',
        'is_manual_price', 'price_updated_at', 'price_updated_by',
        // storefront variant applicability (specific OR all variants)
        'is_all_variants',
        // commerce feature flags (batch-scoped)
        'has_purchase_warranty', 'has_sell_warranty', 'has_wholesale',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining_qty' => 'integer',
            'sn_stock' => 'array',
            'sn_sold' => 'array',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'is_active_for_website' => 'boolean',
            'pos_enabled' => 'boolean',
            'auto_advance' => 'boolean',
            'is_manual_price' => 'boolean',
            'is_all_variants' => 'boolean',
            'has_purchase_warranty' => 'boolean',
            'has_sell_warranty' => 'boolean',
            'has_wholesale' => 'boolean',
            'mfg_date' => 'date',
            'exp_date' => 'date',
            'price_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep batch_sn_lists in sync whenever this batch's serial lists change
        static::saved(function (self $batch) {
            if (!$batch->wasChanged('sn_stock') && !$batch->wasChanged('sn_sold')) {
                return;
            }
            $batch->mirrorSnToList();
        });
    }

    /**
     * Mirror this batch's SN lists (sn_stock / sn_sold) into the dedicated
     * `batch_sn_lists` table so SN history is queryable independently.
     */
    public function mirrorSnToList(): void
    {
        $stock = $this->sn_stock ?? [];
        $sold  = $this->sn_sold ?? [];
        $stock = is_array($stock) ? $stock : [];
        $sold  = is_array($sold) ? $sold : [];

        $hasList = \App\Models\BatchSnList::where('batch_id', $this->id)->exists();
        if (!$stock && !$sold && !$hasList) {
            return; // never had serial numbers — nothing to mirror
        }

        \App\Models\BatchSnList::updateOrCreate(
            ['batch_id' => $this->id],
            [
                'product_id'  => $this->product_id,
                'variant_id'  => $this->variant_price_id,
                'purchase_id' => $this->purchase_id,
                'stock_sn'    => $stock,
                'sold_sn'     => $sold,
            ]
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get remaining supplier warranty days for this batch (if any).
     * If multiple warranties exist for the same purchase+product, pick the
     * newest one (highest id) — not an arbitrary row.
     */
    public function getSupplierWarrantyDaysAttribute(): ?int
    {
        if (!$this->purchase_id) return null;
        $sw = \App\Models\SupplierWarranty::whereHas('purchaseItem', function ($q) {
            $q->where('purchase_id', $this->purchase_id)
              ->where('product_id', $this->product_id);
        })->where('warranty_end_date', '>', now())
          ->latest('id')
          ->first();
        return $sw ? $sw->remaining_days : null;
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }

    // ── Batch-wise pricing engine ──────────────────────────

    public function variantPrices()
    {
        return $this->hasMany(BatchVariantPrice::class, 'stock_batch_id');
    }

    public function wholesalePrices()
    {
        return $this->hasMany(BatchWholesalePrice::class, 'stock_batch_id');
    }

    public function warrantyTiers()
    {
        return $this->hasMany(BatchWarrantyTier::class, 'stock_batch_id');
    }

    public function snList()
    {
        return $this->hasOne(BatchSnList::class, 'batch_id');
    }

    // Scopes

    /** The single batch the website shows & prices from */
    public function scopeActiveForWebsite($query)
    {
        return $query->where('is_active_for_website', true);
    }

    /**
     * Batches that are sellable (not hidden) with remaining stock.
     * D3: `pos_enabled = false` disables a batch for BOTH website and POS —
     * this scope is the shared eligibility filter for web (via PricingService)
     * and POS (`posBatches`), so a disabled batch is excluded from both.
     */
    public function scopeSellable($query)
    {
        return $query->where('pos_enabled', true)
                     ->where('remaining_qty', '>', 0)
                     ->where(function ($q) {
                         $q->whereNull('exp_date')->orWhere('exp_date', '>=', now()->toDateString());
                     });
    }

    /** Batch is sellable AND has remaining stock */
    public function getHasRemainingStockAttribute(): bool
    {
        return (int) $this->remaining_qty > 0;
    }
}
