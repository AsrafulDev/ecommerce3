<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One landing page: a template, its JSON content, one product, and its validity window.
 * Content rules live in App\Services\Landing\ContentValidator.
 */
class LandingTemplate extends Model
{
    protected $table = 'landing_templates';

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'status' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'extra_discount_value' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /** A URL-safe slug from the title, made unique with a numeric suffix when needed. */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'landing';
        $slug = $base;

        for ($n = 2; static::where('slug', $slug)->exists(); $n++) {
            $slug = $base . '-' . $n;
        }

        return $slug;
    }
}
