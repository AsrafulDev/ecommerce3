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

        $cartinfo = Cart::instance('shopping')->add([
            'id'   => $productInfo->id,
            'name' => $productInfo->name,
            'qty'  => $qty,
            'price'=> (float) ($productInfo->new_price ?? $productInfo->old_price ?? 1),
            'options' => [
                'image'          => $productImage,
                'old_price'      => (float) ($productInfo->old_price ?? 0),
                'slug'           => $productInfo->slug,
                'purchase_price' => (float) ($productInfo->purchase_price ?? 0),

                // 🔥 Advance
                'advance_amount' => (float) ($productInfo->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($productInfo->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($productInfo->free_delivery ?? 0),
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

        $today = Carbon::now()->format('Y-m-d');

        if (($coupon->valid_from && $today < $coupon->valid_from) ||
            ($coupon->valid_to && $today > $coupon->valid_to)) {
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

// 🟢 WHOLESALE PRICING — apply if product is wholesale enabled
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
        $price = (float) $wholesaleTier->wholesale_price;
    }
}

        // ✅ Fallback image
        $image = optional($product->image)->image
            ?? DB::table('productimages')->where('product_id', $product->id)->value('image')
            ?? 'public/uploads/default.webp';

        // ✅ Add to cart
        Cart::instance('shopping')->add([
            'id'   => $product->id,
            'name' => $product->name,
            'qty'  => $request->qty ?? 1,
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

    // 🟢 Increment quantity
    public function cart_increment(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $qty  = $item->qty + 1;

        Cart::instance('shopping')->update($request->id, $qty);

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    // 🟢 Decrement quantity
    public function cart_decrement(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $qty  = max(1, $item->qty - 1); // ১ এর নিচে নামবে না

        Cart::instance('shopping')->update($request->id, $qty);

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
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
            ],
        ]);

        $data = Cart::instance('shopping')->content();
        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }
}
