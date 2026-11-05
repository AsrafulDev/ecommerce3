<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;

    protected $table = 'order_details';

    protected static function booted(): void
    {
        // Every creation path (cart, POS, mobile API, incomplete orders) gets the VAT
        // snapshot here, once. An explicit value set by the caller wins.
        static::creating(function (self $detail) {
            if ($detail->vat_amount !== null) {
                return;
            }

            $rate = \App\Models\Product::whereKey($detail->product_id)->value('vat_rate');
            $amount = (float) $detail->sale_price * (int) $detail->qty;

            $snapshot = app(\App\Services\Vat\VatCalculator::class)
                ->snapshot($amount, $rate === null ? null : (float) $rate);

            if ($snapshot) {
                $detail->fill($snapshot);
            }
        });
    }

    protected $fillable = [
        'vat_rate', 'taxable_value', 'vat_amount',
        'order_id',
        'product_id',
        'product_name',
        'purchase_price',
        'sale_price',
        'product_discount',
        'product_size',
        'variant_price_id',
        'product_color',
        'warranty_tier_id',
        'warranty_price',
        'qty',
        'batch_ids',
        'cogs',
    ];

    /**
     * The stock batches this line was taken from, as [{batch_id, qty, unit_cost}]. Null when they were not
     * recorded or do not hold a quantity and a cost for every part. A cost is never filled in from the product's
     * current price: a missing allocation must block the restock, not guess the cost.
     *
     * @return ?list<array{batch_id: mixed, qty: int, unit_cost: float}>
     */
    public function stockAllocation(): ?array
    {
        $recorded = is_array($this->batch_ids) ? $this->batch_ids : json_decode((string) $this->batch_ids, true);
        if (! is_array($recorded) || $recorded === []) {
            return null;
        }

        $parts = [];
        foreach ($recorded as $part) {
            if (! isset($part['qty'], $part['unit_cost']) || (int) $part['qty'] <= 0) {
                return null;
            }
            $parts[] = [
                'batch_id' => $part['batch_id'] ?? null,
                'qty' => (int) $part['qty'],
                'unit_cost' => (float) $part['unit_cost'],
            ];
        }

        return $parts;
    }

    protected function casts(): array
    {
        return [
            'batch_ids' => 'array',
            'cogs'      => 'decimal:2',
        ];
    }

    // ✅ অর্ডারের সাথে রিলেশন
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // ✅ প্রোডাক্ট রিলেশন (ডিজিটাল চেক করার জন্য খুব জরুরি)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
        // চাইলে select ব্যবহার করতে পারো:
        // ->select('id', 'name', 'slug', 'is_digital', 'digital_file', 'download_limit', 'download_expire_days');
    }

    // ✅ কালার রিলেশন (product_color = Color ID)
    public function color()
    {
        return $this->belongsTo(Color::class, 'product_color', 'id');
    }

    // ✅ সাইজ রিলেশন (product_size = Size ID)
    public function size()
    {
        return $this->belongsTo(Size::class, 'product_size', 'id');
    }

    // ✅ ইমেজ (প্রোডাক্ট আইডি দিয়ে)
    public function image()
    {
        return $this->belongsTo(Productimage::class, 'product_id', 'product_id')
                    ->select('id', 'product_id', 'image');
    }

    // ── Warranty ──────────────────────────────

    public function warrantySale()
    {
        return $this->hasOne(WarrantySale::class, 'order_detail_id');
    }
}
