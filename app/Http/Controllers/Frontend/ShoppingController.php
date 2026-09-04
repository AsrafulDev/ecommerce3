<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductVariantPrice;
use App\Models\Product;
use App\Models\ProductWholesalePrice;
use App\Models\Coupon;
use Toastr;
use Cart;
use DB;
use Carbon\Carbon;
use Session;

class ShoppingController extends Controller
{
    /**
     * 🔹 কার্টে থাকা সব প্রোডাক্ট থেকে মোট Advance Amount বের করবে
     */
    public static function getCartAdvanceAmount()
    {
        $advance = 0;

        // ✅ First collect all product IDs to avoid N+1 query
        $productIds = Cart::instance('shopping')->content()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($productIds)) {
            return $advance;
        }

        // ✅ Load all products in a single query
        $products = Product::whereIn('id', $productIds)
            ->select('id', 'advance_amount')
            ->get()
            ->keyBy('id'); // Key by ID for fast lookup

        // ✅ Now iterate through cart items without additional queries
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = $products->get($item->id);

            if ($product && $product->advance_amount > 0) {
                // Qty অনুযায়ী গুণ করব
                $advance += ($product->advance_amount * $item->qty);
            }
        }

        return $advance;
    }

    /**
     * ⭐ নতুন helper:
     * 🔹 কার্টে অন্তত একটি ডিজিটাল প্রোডাক্ট আছে কি না?
     */
    public static function hasDigitalProductInCart()
    {
        foreach (Cart::instance('shopping')->content() as $item) {
            if (!empty($item->options->is_digital) && $item->options->is_digital == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * ⭐ নতুন helper:
     * 🔹 কার্টে থাকা সব প্রোডাক্ট free delivery eligible কিনা check করবে
     * যদি সব প্রোডাক্ট free_delivery = 1 হয়, তাহলে shipping charge 0 হবে
     */
    public static function hasAllFreeDeliveryProducts()
    {
        $productIds = Cart::instance('shopping')->content()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($productIds)) {
            return false;
        }

        // Load all products in a single query
        $products = Product::whereIn('id', $productIds)
            ->select('id', 'free_delivery', 'is_digital')
            ->get()
            ->keyBy('id');

        // Check if all physical products have free_delivery = 1
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = $products->get($item->id);
            
            // Digital products don't need shipping, so skip them
            if ($product && $product->is_digital == 1) {
                continue;
            }
            
            // If any physical product doesn't have free_delivery, return false
            if ($product && $product->free_delivery != 1) {
                return false;
            }
        }

        return true;
    }

    // 🟢 Add to cart (GET)
    public function addTocartGet($id, Request $request)
    {
        $qty = 1;
        $productInfo = Product::find($id);

        if (!$productInfo) {
            return response()->json(['error' => 'Product not found']);
        }

        $productImage = DB::table('productimages')
            ->where('product_id', $id)
            ->value('image') ?? 'public/uploads/default.webp';

        // ⭐ Batch-wise pricing engine — resolve from the active website batch
        $pricing   = app(\App\Services\PricingService::class);
        $batchWise = $pricing->isBatchWise();
        if ($batchWise && !$pricing->isWebsiteSellable($productInfo)) {
            Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
            return response()->json(['error' => 'Stock out']);
        }
        $sellPrice = $batchWise
            ? max($pricing->price($productInfo, null, null, 'website'), 1)
            : (float) ($productInfo->new_price ?? $productInfo->old_price ?? 1);

        $cartinfo = Cart::instance('shopping')->add([
            'id'   => $productInfo->id,
            'name' => $productInfo->name,
            'qty'  => $qty,
            'price'=> $sellPrice,
            'options' => [
                'image'          => $productImage,
                'old_price'      => $batchWise ? ($pricing->mrp($productInfo) ?? 0) : (float) ($productInfo->old_price ?? 0),
                'slug'           => $productInfo->slug,
                'purchase_price' => (float) ($productInfo->purchase_price ?? 0),

                // 🔥 Advance
                'advance_amount' => (float) ($productInfo->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($productInfo->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($productInfo->free_delivery ?? 0),

                // 🏷️ Original prices
                'regular_price'       => $batchWise ? ($pricing->mrp($productInfo) ?? 0) : (float) ($productInfo->old_price ?? 0),
                'sale_price'          => $batchWise ? $sellPrice : (float) ($productInfo->new_price ?? 0),
                'base_price'          => $sellPrice,

                // ⭐ Batch-wise pricing engine
                'batch_id'            => $batchWise ? ($pricing->activeWebsiteBatch($productInfo)?->id ?? null) : null,

                // 🛡️ Warranty
                'warranty_tier_id'    => null,
                'warranty_adjustment' => 0,
                'wholesale_discount'  => 0,
            ],
        ]);

        return response()->json($cartinfo);
    }

    // 🟢 Apply coupon
    public function applyCoupon(Request $request)
    {
        $request->validate(['coupon_code' => 'required']);

        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('status', 1)
            ->first();

        if (!$coupon) {
            Toastr::error('Invalid Coupon Code', 'Error');
            return redirect()->back();
        }

        // Compare dates as Carbon (valid_from/valid_to are cast to date), not as
        // string vs Carbon — otherwise a coupon valid_from = today is wrongly
        // rejected ('2026-08-03' < '2026-08-03 00:00:00' is true in PHP).
        $now = Carbon::now()->startOfDay();

        if (($coupon->valid_from && $coupon->valid_from->startOfDay()->gt($now)) ||
            ($coupon->valid_to && $coupon->valid_to->startOfDay()->lt($now))) {
            Toastr::error('Coupon expired or not valid yet', 'Error');
            return redirect()->back();
        }

        // subtotal() returns string like “1,200.00”
        $subtotal = floatval(
            preg_replace('/[^\d.]/', '', Cart::instance('shopping')->subtotal())
        );

        if ($coupon->min_purchase && $subtotal < $coupon->min_purchase) {
            Toastr::error("Minimum purchase ৳{$coupon->min_purchase} required", 'Error');
            return redirect()->back();
        }

        // Enforce max uses at apply-time (pre-check). Final atomic increment occurs at order save.
        if (!empty($coupon->max_uses) && (int) $coupon->max_uses > 0 && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            Toastr::error('Coupon usage limit reached', 'Error');
            return redirect()->back();
        }

        $discount = $coupon->type == 'percent'
            ? ($subtotal * ($coupon->value / 100))
            : $coupon->value;

        Session::put('coupon_code', $coupon->code);
        Session::put('discount', round($discount, 2));

        Toastr::success("Coupon Applied! You saved ৳" . round($discount, 2), 'Success');
        return redirect()->back();
    }

    // 🟢 Remove coupon
    public function removeCoupon()
    {
        Session::forget(['coupon_code', 'discount']);
        Toastr::success('Coupon removed successfully', 'Success');
        return redirect()->back();
    }

    // 🟢 Cart page
    public function cart_show()
    {
        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.pages.cart', compact('data'));
    }

    // 🟢 Add to cart (POST) with variant support
    public function cart_store(Request $request)
    {
        $product = Product::with('image')->find($request->id);

        if (!$product) {
            Toastr::error('Product not found', 'Error!');
            return redirect()->back();
        }

        $price = 0;
        $variantId = null;

// color + size
if ($request->filled('product_color') && $request->filled('product_size')) {
    $variant = ProductVariantPrice::where('product_id', $product->id)
        ->where('color_id', $request->product_color)
        ->where('size_id', $request->product_size)
        ->first();

    if ($variant) {
        $price = (float) $variant->price;
        $variantId = $variant->id;
    }
}

// only color
elseif ($request->filled('product_color')) {
    $variant = ProductVariantPrice::where('product_id', $product->id)
        ->where('color_id', $request->product_color)
        ->whereNull('size_id')
        ->first();

    if ($variant) {
        $price = (float) $variant->price;
        $variantId = $variant->id;
    }
}

// only size
elseif ($request->filled('product_size')) {
    $variant = ProductVariantPrice::where('product_id', $product->id)
        ->where('size_id', $request->product_size)
        ->whereNull('color_id')
        ->first();

    if ($variant) {
        $price = (float) $variant->price;
        $variantId = $variant->id;
    }
}

// fallback
if ($price <= 0) {
    $price = (float) ($product->new_price ?? $product->old_price ?? 1);
}

// 🛡️ WARRANTY — apply adjustment (NOT replacement)
$warrantyTier = null;
$warrantyAdjustment = 0;
$wholesaleDiscount = 0;
if ($request->filled('warranty_tier_id')) {
    $warrantyTier = \App\Models\ProductWarrantyTier::find($request->warranty_tier_id);
    if ($warrantyTier && $warrantyTier->is_active) {
        $warrantyAdjustment = (float) ($warrantyTier->additional_cost ?? 0);
        $price += $warrantyAdjustment;
    }
}

// �🟢 WHOLESALE PRICING — apply if product is wholesale enabled
if ($product->is_wholesale) {
    $qty = (int) ($request->qty ?? 1);

    // Priority 1: variant-specific tier (exact match on variant_id)
    $wholesaleTier = ProductWholesalePrice::where('product_id', $product->id)
        ->where('variant_id', $variantId)
        ->where('min_quantity', '<=', $qty)
        ->where(function ($q) use ($qty) {
            $q->whereNull('max_quantity')
              ->orWhere('max_quantity', '>=', $qty);
        })
        ->orderBy('min_quantity', 'desc')
        ->first();

    // Priority 2: fallback to global tier (variant_id = null)
    if (! $wholesaleTier) {
        $wholesaleTier = ProductWholesalePrice::where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('min_quantity', '<=', $qty)
            ->where(function ($q) use ($qty) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $qty);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();
    }

    if ($wholesaleTier) {
        $wholesaleDiscount = (float) ($wholesaleTier->wholesale_price ?? 0);
        $price -= $wholesaleDiscount;
    }
}

        // ✅ Fallback image
        $image = optional($product->image)->image
            ?? DB::table('productimages')->where('product_id', $product->id)->value('image')
            ?? 'public/uploads/default.webp';

        // 🚦 Stock ceiling — the qty +/- stepper enforces this (availableStockForItem),
        //    but a direct "Buy Now" / qty-input add bypassed it entirely, letting a
        //    product without allow_negative_stock be oversold straight from the PDP.
        $qty = max(1, (int) ($request->qty ?? 1));
        $pricing = app(\App\Services\PricingService::class);
        $existingQty = (int) Cart::instance('shopping')->content()
            ->filter(fn ($line) => $line->id == $product->id
                && ($line->options->product_size ?? null) == ($request->product_size ?? null)
                && ($line->options->product_color ?? null) == ($request->product_color ?? null))
            ->sum('qty');
        $max = $pricing->maxOrderableQty($product, 'website', null, $variantId);
        if ($max !== null) {
            if ($max <= $existingQty) {
                Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
                return redirect()->back();
            }
            if ($existingQty + $qty > $max) {
                $qty = $max - $existingQty;
                Toastr::error('স্টকে যত আছে তার বেশি অর্ডার করা যাবে না। সর্বোচ্চ ' . $max . ' টি নিতে পারবেন।', 'স্টক সীমা!');
            }
        }

        // ✅ Add to cart
        Cart::instance('shopping')->add([
            'id'   => $product->id,
            'name' => $product->name,
            'qty'  => $qty,
            'price'=> $price,
            'options' => [
                'slug'           => $product->slug,
                'image'          => $image,
                'old_price'      => (float) ($product->old_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'product_size'   => $request->product_size ?? null,
                'product_color'  => $request->product_color ?? null,
                'pro_unit'       => $request->pro_unit ?? null,

                // 🔥 Advance
                'advance_amount' => (float) ($product->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($product->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($product->free_delivery ?? 0),

                // 🏷️ Original prices (before warranty/wholesale adjustments)
                'regular_price'       => (float) ($product->old_price ?? 0),
                'sale_price'          => (float) ($product->new_price ?? 0),
                'base_price'          => $product->new_price ?? $product->old_price ?? 0,

                // 🛡️ Warranty
                'warranty_tier_id'    => $request->warranty_tier_id ?? null,
                'warranty_adjustment' => $warrantyAdjustment,
                'wholesale_discount' => $wholesaleDiscount,
            ],
        ]);

        Toastr::success('Product added to cart successfully!', 'Success');

        // যদি ফর্ম থেকে "order_now" ক্লিক করা হয়ে থাকে, সরাসরি checkout
        if ($request->has('order_now')) {
            return redirect()->route('customer.checkout');
        }

        // নরমাল কেসে আগের পেইজে ফিরে যাবে
        return redirect()->back();
    }

    // 🟢 Update cart (color/size change)
    public function cart_update(Request $request)
    {
        $rowId    = $request->id;
        $cartItem = Cart::instance('shopping')->get($rowId);

        if ($cartItem) {
            Cart::instance('shopping')->update($rowId, [
                'options' => [
                    'product_size'   => $request->product_size ?: $cartItem->options->product_size,
                    'product_color'  => $request->product_color ?: $cartItem->options->product_color,
                    'slug'           => $cartItem->options->slug,
                    'image'          => $cartItem->options->image,
                    'old_price'      => $cartItem->options->old_price,
                    'purchase_price' => $cartItem->options->purchase_price,
                    'pro_unit'       => $cartItem->options->pro_unit,

                    // 🔥 পুরানো advance_amount টাকে রেখে দাও
                    'advance_amount' => $cartItem->options->advance_amount ?? 0,

                    // 🔥 Digital flag আগের মতোই থাকবে
                    'is_digital'     => $cartItem->options->is_digital ?? 0,

                    // 🔥 Free Delivery flag আগের মতোই থাকবে
                    'free_delivery'  => $cartItem->options->free_delivery ?? 0,

                    // 🏷️ Original prices — preserve on cart update
                    'regular_price'       => $cartItem->options->regular_price ?? 0,
                    'sale_price'          => $cartItem->options->sale_price ?? 0,
                    'base_price'          => $cartItem->options->base_price ?? 0,

                    // 🛡️ Warranty — preserve on cart update
                    'warranty_tier_id'    => $cartItem->options->warranty_tier_id ?? null,
                    'warranty_adjustment' => $cartItem->options->warranty_adjustment ?? 0,
                    'wholesale_discount'  => $cartItem->options->wholesale_discount ?? 0,
                ],
            ]);
        }

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    // 🟢 Remove from cart
    public function cart_remove(Request $request)
    {
        Cart::instance('shopping')->update($request->id, 0);
        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    // 🟢 Increment quantity — enforces the same stock limit the product page
    //    add-to-cart uses, and reprices from the ACTUAL pricing method when a qty
    //    change crosses a wholesale tier.
    public function cart_increment(Request $request)
    {
        $cart  = Cart::instance('shopping');
        $item  = $cart->get($request->id);
        $qty   = (int) $item->qty + 1;
        $max   = $this->availableStockForItem($item);
        $blocked = false;

        // 🚦 Side cart / checkout MUST enforce the same "max X" stock rule as the
        //    product page — otherwise a user can keep pressing + past the real
        //    (batch/website) stock and oversell.
        if ($max !== null && $max >= 1 && $qty > $max) {
            $blocked = true;
            Toastr::error(
                'স্টকে যত আছে তার বেশি অর্ডার করা যাবে না। সর্বোচ্চ ' . $max . ' টি নিতে পারবেন।',
                'স্টক সীমা!'
            );
        } elseif ($max !== null && $max < 1) {
            // No sellable stock left — never allow increasing an OOS line.
            $blocked = true;
            Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
        } else {
            $this->repriceCartRow($item, $qty);
        }

        $data = Cart::instance('shopping')->content();
        $view = view('frontEnd.layouts.ajax.cart', compact('data'));

        if ($blocked) {
            // Full-page reloads (e.g. checkout) show the flashed Toastr from the
            // session; AJAX refreshes (side cart) read this header to toast now.
            return response($view->render())->withHeaders(['X-Cart-Stock-Limit' => (string) $max]);
        }

        return $view;
    }

    /**
     * How many units of this cart line's product can still be ordered — the SAME
     * number the product page add-to-cart caps at:
     *   • batch-wise (default): sum of sellable (website-enabled) batch stock,
     *     variant-aware when the row carries a variant.
     *   • legacy: products.stock.
     * Returns null when the product is gone, OR when it has
     * `allow_negative_stock` enabled — either way, no limit should be enforced.
     */
    protected function availableStockForItem($item): ?int
    {
        if (!$item) {
            return null;
        }
        $product = Product::find($item->id);
        if (!$product) {
            return null;
        }
        $pricing   = app(\App\Services\PricingService::class);
        $variantId = $item->options->variant_price_id ?? null;
        $max       = $pricing->maxOrderableQty($product, 'website', null, $variantId);
        return $max === null ? null : max(0, $max);
    }

    // 🟢 Decrement quantity — same reprice so dropping below a wholesale tier
    //    (e.g. 2–5 pcs → 1 pc) restores the actual full unit price.
    public function cart_decrement(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $this->repriceCartRow($item, max(1, (int) $item->qty - 1)); // ১ এর নিচে নামবে না

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    // ⭐ Per-batch billing helper — active when PRICING_MULTI_BATCH_PRICING=per_batch.
    protected function isPerBatchMode(): bool
    {
        return app(\App\Services\PricingService::class)->isBatchWise()
            && config('pricing.multi_batch_pricing', 'active_batch') === 'per_batch';
    }

    /**
     * ⭐ Reprice a cart row for a new qty using the SAME pricing method used at
     *    add-to-cart. Previously qty changes only repriced under per-batch billing
     *    (and even then re-used the stale wholesale/warranty amounts), so dropping
     *    below a quantity-discount tier (e.g. 2–5 pcs → 1 pc) kept the discounted
     *    unit price. Now the base price, wholesale discount and warranty are all
     *    re-derived for the new qty from the product's actual (active) batch:
     *        unit = base + warranty − wholesale(new qty)
     */
    protected function repriceCartRow($item, int $qty): void
    {
        if (!$item) {
            return;
        }
        $cart    = Cart::instance('shopping');
        $pricing = app(\App\Services\PricingService::class);
        $product = Product::find($item->id);

        if (!$product || $qty <= 0) {
            $cart->update($item->rowId, ['qty' => max(1, $qty)]);
            return;
        }

        $variantId      = $item->options->variant_price_id ?? null;
        $warrantyTierId = $item->options->warranty_tier_id ?? null;

        // 1) Base unit price — before warranty/wholesale. Uses the current active
        //    website batch (the "actual" storefront price), or the qty-weighted
        //    average across eligible batches under per-batch billing.
        $base = $this->isPerBatchMode()
            ? $pricing->weightedAllocationUnit($product, $qty, $variantId, $pricing->allocationMethod($product))
            : $pricing->price($product, null, $variantId, 'website');

        if ($base <= 0) {
            // Out of stock / not allocatable — keep the row's previous value.
            $base = (float) ($item->options->base_price ?? $item->price);
            $base = $base > 0 ? $base : 1;
        }

        // 2) Warranty surcharge (re-derived for the resolved batch/tier).
        $warrantyAdj = (float) ($item->options->warranty_adjustment ?? 0);
        if ($warrantyTierId) {
            $warrantyAdj = $pricing->warrantyAdjustment($product, $warrantyTierId, null, $variantId);
        }

        // 3) Wholesale quantity-discount for the NEW qty — 0 when the qty no
        //    longer falls inside a tier (this is the piece that was missing).
        $wholesale = $pricing->wholesale($product, $qty, null, $variantId);

        $unit = max(0, round($base + $warrantyAdj - $wholesale, 2));

        // Update the line price + the option fields the cart/checkout read, so the
        //   discount badge, strike-through base price and OrderHelper stay in sync.
        $options = $item->options->all();
        $options['base_price']          = $base;
        $options['sale_price']          = $base;
        $options['regular_price']       = (float) ($options['regular_price'] ?? $base);
        $options['warranty_adjustment'] = $warrantyAdj;
        $options['wholesale_discount']  = $wholesale;

        $cart->update($item->rowId, [
            'qty'     => $qty,
            'price'   => $unit,
            'options' => $options,
        ]);
    }

    // 🟢 Cart count (header)
    public function cart_count(Request $request)
    {
        $data = Cart::instance('shopping')->count();
        return view('frontEnd.layouts.ajax.cart_count', compact('data'));
    }

    // 🟢 Mobile cart count
    public function mobilecart_qty(Request $request)
    {
        $data = Cart::instance('shopping')->count();
        return view('frontEnd.layouts.ajax.mobilecart_qty', compact('data'));
    }

    /**
     * Sidebar cart – ডান দিক থেকে স্লাইড আউট হওয়া কার্ট (AJAX)
     */
    public function cartSidebar(Request $request)
    {
        $cartContent   = Cart::instance('shopping')->content();
        $subtotal      = (float) str_replace(',', '', Cart::instance('shopping')->subtotal());
        $generalsetting = \App\Models\GeneralSetting::first();
        return view('frontEnd.layouts.ajax.cart_sidebar', compact('cartContent', 'subtotal', 'generalsetting'));
    }

    // 🟢 Change product from campaign or offers
    public function changeProduct(Request $request)
    {
        $productId = $request->input('id');
        $product   = Product::with('image')->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }

        Cart::instance('shopping')->destroy();

        Cart::instance('shopping')->add([
            'id'   => $product->id,
            'name' => $product->name,
            'qty'  => 1,
            'price'=> (float) ($product->new_price ?? $product->old_price ?? 1),
            'options' => [
                'slug'           => $product->slug,
                'image'          => optional($product->image)->image ?? 'public/uploads/default.webp',
                'old_price'      => (float) ($product->old_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),

                // 🔥 Advance
                'advance_amount' => (float) ($product->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($product->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($product->free_delivery ?? 0),

                // 🏷️ Original prices
                'regular_price'       => (float) ($product->old_price ?? 0),
                'sale_price'          => (float) ($product->new_price ?? 0),
                'base_price'          => (float) ($product->new_price ?? $product->old_price ?? 1),

                // 🛡️ Warranty
                'warranty_tier_id'    => null,
                'warranty_adjustment' => 0,
                'wholesale_discount'  => 0,
            ],
        ]);

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }
}
