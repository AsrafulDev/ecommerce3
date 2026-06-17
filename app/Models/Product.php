<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // সব ফিল্ড mass assign করতে পারো (যেমন আগে ছিল)
    protected $guarded = [];

    // Laravel 12: Use casts() method instead of $casts property
    protected function casts(): array
    {
        return [
            'is_digital'           => 'boolean',
            'download_limit'       => 'integer',
            'download_expire_days' => 'integer',
            'is_wholesale'         => 'boolean',
            'min_wholesale_quantity' => 'integer',
            'free_delivery'        => 'boolean',
        ];
    }

    // route model binding এ slug ব্যবহার
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // ------------------------
    // RELATIONS
    // ------------------------

    public function image()
    {
        return $this->hasOne(Productimage::class, 'product_id')
                    ->select('id', 'image', 'product_id', 'color_id', 'size_id');
    }

    public function images()
    {
        return $this->hasMany(Productimage::class, 'product_id')
                    ->select('id', 'image', 'product_id', 'color_id', 'size_id');
    }

    /** Default images (no color/size) */
    public function defaultImages()
    {
        return $this->hasMany(Productimage::class, 'product_id')
                    ->whereNull('color_id')->whereNull('size_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id')
                    ->select('id', 'product_id', 'ratting');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id')
                    ->select('id', 'name', 'slug');
    }

    public function subcategory()
    {
        return $this->hasOne(Subcategory::class, 'id', 'subcategory_id')
                    ->select('id', 'subcategoryName', 'slug');
    }

    public function childcategory()
    {
        return $this->hasOne(Childcategory::class, 'id', 'childcategory_id')
                    ->select('id', 'childcategoryName', 'slug');
    }

    public function brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id')
                    ->select('id', 'name', 'slug');
    }

    /**
     * Vendor that owns the product.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'productsizes')->withTimestamps();
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'productcolors')->withTimestamps();
    }

    public function prosizes()
    {
        return $this->hasMany(Productsize::class);
    }

    public function procolors()
    {
        return $this->hasMany(Productcolor::class);
    }

    public function prosize()
    {
        return $this->hasOne(Productsize::class, 'product_id');
    }

    public function procolor()
    {
        return $this->hasOne(Productcolor::class, 'product_id');
    }

    public function variantPrices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id');
    }

    public function wholesalePrices()
    {
        return $this->hasMany(ProductWholesalePrice::class, 'product_id')->orderBy('min_quantity', 'asc');
    }

    // ------------------------
    // Digital product related
    // ------------------------

    // চাইলে Product থেকে কোন কোন DigitalDownload আছে দেখতে পারবে
    public function digitalDownloads()
    {
        return $this->hasMany(DigitalDownload::class, 'product_id');
    }

    // হেল্পার: এই প্রোডাক্ট ডিজিটাল কি না
    public function isDigital(): bool
    {
        return (bool) $this->is_digital;
    }

    // হেল্পার: এই প্রোডাক্ট wholesale কি না
    public function isWholesale(): bool
    {
        return (bool) $this->is_wholesale;
    }

    // Scope: Wholesale products
    public function scopeWholesale($query)
    {
        return $query->where('is_wholesale', 1);
    }

    // Scope: Regular products
    public function scopeRegular($query)
    {
        return $query->where('is_wholesale', 0);
    }
}
