<?php

namespace App\Http\Controllers\Frontend;

use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Childcategory;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ProductWholesalePrice;
use App\Models\Size;
use App\Models\Color;
use App\Models\District;
use App\Models\CreatePage;
use App\Models\Campaign;
use App\Models\Banner;
use App\Models\ShippingCharge;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\Customer;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Review;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use App\Models\HomepageLayout;
use Session;
use Cart;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Helpers\OrderHelper;
use App\Models\Brand;
use App\Models\Blog;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;


class FrontendController extends Controller
{
    public function index()
    {
        // ✅ Homepage cache (5 min) - reduces DB load on high traffic
        $cacheKey = 'frontend_homepage_v1';
        $cacheMinutes = 5;
        $data = Cache::remember($cacheKey, $cacheMinutes * 60, function () {
            return $this->getHomepageData();
        });
        return view('frontEnd.layouts.pages.index', $data);
    }

    /**
     * Homepage data (used for cache)
     */
    protected function getHomepageData()
    {
        // General setting
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();

        // SEO setting
        $seo = DB::table('seo_settings')->first();

        // Main menu categories (for header/sidebar)
        $menucategories = Category::where('status', 1)
            ->where('parent_id', 0)
            ->select('id', 'name', 'slug', 'image')
            ->with(['subcategories.childcategories'])
            ->orderBy('id', 'ASC')
            ->get();

        // Front categories (যদি অন্য কোথাও ব্যবহার হয়)
        $frontcategory = Category::where(['status' => 1])
            ->select('id', 'name', 'image', 'slug', 'status')
            ->get();

        // Banners
        $sliders = Banner::where(['status' => 1, 'category_id' => 1])
            ->select('id', 'image', 'link')
            ->get();
$brands = Brand::where('status', 1)
    ->select('id', 'name', 'slug', 'image')
	 ->limit(12) 
    ->get();
    $blogs = Blog::where('status', 1)
        ->latest()
        ->limit(3)
        ->get();
        $campaognads = Banner::where(['status' => 1, 'category_id' => 7])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $sliderbottomads = Banner::where(['status' => 1, 'category_id' => 5])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $footertopads = Banner::where(['status' => 1, 'category_id' => 6])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $homepageads = Banner::where(['status' => 1, 'category_id' => 10])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $homepageads2 = Banner::where(['status' => 1, 'category_id' => 11])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $hitdealsbaner = Banner::where(['status' => 1, 'category_id' => 9])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        // Flash sale – image + reviews eager load
        $flas_sales = Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with(['prosizes', 'procolors', 'image', 'reviews'])
            ->limit(12)
            ->get();

        // Hot deal top – image + reviews eager load
        $hotdeal_top = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with(['prosizes', 'procolors', 'image', 'reviews'])
            ->limit(12)
            ->get();

        $hotdeal_bottom = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with('image')
            ->skip(12)
            ->limit(12)
            ->get();

        // Category wise home products – products এর image + reviews eager load
        if ($generalsetting && $generalsetting->show_category_wise_products) {
            $homeproducts = Category::where('status', 1)
                ->orderBy('id', 'ASC')
                ->with([
                    'products' => function ($q) {
                        $q->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'stock')
                            ->where('status', 1)
                            ->where('approval_status', 'approved')
                            ->with(['image', 'prosizes', 'procolors', 'reviews'])
                            ->limit(12);
                    }
                ])
                ->get()
                ->map(function ($query) {
                    // প্রতি ক্যাটাগরিতে ১২টা প্রোডাক্ট দেখাবো
                    $query->setRelation('products', $query->products->take(12));
                    return $query;
                });
        } else {
            $homeproducts = null;
        }

        $reviews = Banner::where(['status' => 1, 'category_id' => 8])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        // All products – image + reviews eager load (যদি হোমে দরকার হয়)
        if ($generalsetting && $generalsetting->show_all_products) {
            $all_products = Product::where(['status' => 1, 'approval_status' => 'approved'])
                ->inRandomOrder()
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
                ->with(['prosizes', 'procolors', 'image', 'reviews'])
                ->limit(14)
                ->get();
        } else {
            $all_products = null;
        }

        // Active homepage layout (for drag-drop section builder)
        $activeLayout = HomepageLayout::getActive();

        // Determine if flash sale / hot deal are still active based on end dates
        $isHotDealActive = false;
        $isFlashSaleActive = false;
        if ($generalsetting) {
            $hotDealEndDate = $generalsetting->hot_deal_end_date
                ? \Carbon\Carbon::parse($generalsetting->hot_deal_end_date)->format('Y-m-d') . 'T23:59:59'
                : null;
            $flashSaleEndDate = $generalsetting->flash_sale_end_date
                ? \Carbon\Carbon::parse($generalsetting->flash_sale_end_date)->format('Y-m-d') . 'T23:59:59'
                : null;
            $isHotDealActive = $hotDealEndDate && \Carbon\Carbon::parse($hotDealEndDate)->isFuture();
            $isFlashSaleActive = $flashSaleEndDate && \Carbon\Carbon::parse($flashSaleEndDate)->isFuture();
        }

        // ⭐ Batch-wise pricing: attach storefront price ranges so product cards
        //    render ৳10 - ৳12 (range) instead of a static single price.
        $pricingSvc = app(\App\Services\PricingService::class);
        foreach (['flas_sales', 'hotdeal_top', 'hotdeal_bottom', 'all_products'] as $var) {
            if ($$var !== null) {
                $pricingSvc->attachCatalogRanges($$var);
            }
        }
        if ($homeproducts) {
            $homeproducts->each(function ($cat) use ($pricingSvc) {
                $pricingSvc->attachCatalogRanges($cat->products);
            });
        }

        return compact(
            'seo',
            'generalsetting',
            'menucategories',
            'sliders',
            'brands',
            'blogs',
            'frontcategory',
            'hotdeal_top',
            'hotdeal_bottom',
            'homeproducts',
            'sliderbottomads',
            'footertopads',
            'homepageads2',
            'hitdealsbaner',
            'homepageads',
            'flas_sales',
            'campaognads',
            'reviews',
            'all_products',
            'activeLayout',
            'isHotDealActive',
            'isFlashSaleActive'
        );
    }

    /**
     * ⭐ Batch-wise catalog helpers (Phase 2.2/2.3).
     * When BATCH_WISE_PRICING is ON, catalog sorting + the price slider/filter
     * operate on each product's LOWEST sellable batch sale price (MIN of
     * stock_batches.selling_price) instead of the static products.new_price.
     * With the flag OFF these helpers no-op → legacy new_price behaviour.
     */

    protected function batchCatalogEnabled(): bool
    {
        return app(\App\Services\PricingService::class)->isBatchWise();
    }

    /**
     * Per-product grouped subquery: product_id → MIN(sellable batch selling_price).
     * Only sellable batches (pos_enabled + remaining_qty>0 + not expired + priced).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function batchMinSaleSub()
    {
        return \App\Models\StockBatch::query()
            ->selectRaw('stock_batches.product_id AS product_id')
            ->selectRaw('MIN(stock_batches.selling_price) AS min_sale')
            ->where('stock_batches.pos_enabled', true)
            ->where('stock_batches.remaining_qty', '>', 0)
            ->where('stock_batches.selling_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('stock_batches.exp_date')
                  ->orWhere('stock_batches.exp_date', '>=', now()->toDateString());
            })
            ->groupBy('stock_batches.product_id');
    }

    /**
     * LEFT JOIN the per-product batch min-sale onto a Product query so its rows can
     * be ordered / filtered by batch price. Products without a sellable batch keep
     * a NULL min_sale (they stay visible, like the “stock out” card state).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function joinBatchMinSale($query)
    {
        return $query->leftJoinSub($this->batchMinSaleSub(), 'bm', function ($join) {
            $join->on('products.id', '=', 'bm.product_id');
        });
    }

    /**
     * Batch-aware price-sort column name (alias 'bm.min_sale' or legacy 'new_price').
     */
    protected function batchPriceSortColumn(): string
    {
        return $this->batchCatalogEnabled() ? 'bm.min_sale' : 'new_price';
    }

    // ===========================
    // Add to cart with variant + stock check
    // ===========================
    public function cartStore(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer',
            'qty'           => 'nullable|integer|min:1',
            'product_color' => 'nullable|integer',
            'product_size'  => 'nullable|integer',
            'warranty_tier_id' => 'nullable|integer',
            'order_now'     => 'nullable|boolean',
        ]);
        
        // =========================================================
        // [START] এডমিন প্যানেল থেকে সেট করা ডাইনামিক লিমিট লজিক
        // =========================================================
        
        // ১. ডাটাবেস থেকে সেটিং লোড করা
        $setting = GeneralSetting::select('order_limit_time', 'order_limit_qty')->first();
        
        // যদি সেটিং না পায় বা ভ্যালু না থাকে, তবে ডিফল্ট হিসেবে ৪৮ ঘন্টা এবং ২ বার ধরবে
        $limitHours = $setting?->order_limit_time ?? 48; 
        $limitQty   = $setting?->order_limit_qty ?? 2;

        $productId = $request->id;
        // ডাইনামিক সময় ক্যালকুলেশন
        $timeLimit = Carbon::now()->subHours($limitHours); 
        $currentIp = $request->ip();

        // কুয়েরি তৈরি
        $query = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('order_details.product_id', $productId)
            ->where('orders.created_at', '>=', $timeLimit);

        // ইউজার বা আইপি চেক
        if (Auth::guard('customer')->check()) {
            $customerId = Auth::guard('customer')->user()->id;
            $query->where('orders.customer_id', $customerId);
        } else {
            // [সতর্কতা] আপনার ডাটাবেসে কলামের নাম 'ip_address' না 'ip' সেটা নিশ্চিত হয়ে নিবেন
            $query->where('orders.ip_address', $currentIp); 
        }

        // মোট কতবার অর্ডার করেছে তা গণনা
        $orderCount = $query->count();

        // যদি লিমিটের সমান বা বেশি হয়, তবে আটকাবে
        if ($orderCount >= $limitQty) {
           return redirect()->back()->with('show_order_limit_modal', true);
        }
        
        // =========================================================
        // [END] লজিক শেষ
        // =========================================================

        $product = Product::with('image')->findOrFail($request->id);

        // 1) প্রোডাক্টের স্টক বের করি
        $availableStock = $this->getAvailableStock($product);
        $requestedQty   = max(1, (int)($request->qty ?? 1));

        // যদি স্টকের কোন কলামই না থাকে (stock/qty/quantity নেই), তখন স্টক চেক স্কিপ করবে
        if ($availableStock !== null) {

            // স্টক ০ বা কম হলে সরাসরি ব্লক
            if ($availableStock <= 0) {
                Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
                return redirect()->back()->withInput();
            }

            // কার্টে আগে থেকে একই প্রোডাক্ট (একই ভ্যারিয়েন্ট) কত qty আছে, সেটা বের করি
            $alreadyInCart = Cart::instance('shopping')
                ->search(function ($cartItem, $rowId) use ($product, $request) {
                    if ($cartItem->id != $product->id) {
                        return false;
                    }

                    $colorId = $request->product_color ?? null;
                    $sizeId  = $request->product_size ?? null;

                    return ($cartItem->options->color_id ?? null) == $colorId
                        && ($cartItem->options->size_id ?? null) == $sizeId;
                })
                ->sum('qty');

            $totalRequested = $alreadyInCart + $requestedQty;

            // স্টকের চেয়ে বেশি চাইলে error
            if ($totalRequested > $availableStock) {
                Toastr::error(
                    'স্টকে যত আছে তার বেশি অর্ডার করা যাবে না। সর্বোচ্চ ' . $availableStock . ' টি নিতে পারবেন।',
                    'স্টক সীমা!'
                );
                return redirect()->back()->withInput();
            }
        }

        // Variant price খুঁজে বের করো
        $variantPrice = ProductVariantPrice::where('product_id', $product->id)
            ->when($request->product_color, function ($q) use ($request) {
                $q->where('color_id', $request->product_color);
            })
            ->when($request->product_size, function ($q) use ($request) {
                $q->where('size_id', $request->product_size);
            })
            ->first();

        // ⭐ Batch-wise pricing engine — resolve price from the active website batch
        $pricing     = app(\App\Services\PricingService::class);
        $batchWise   = $pricing->isBatchWise();
        $variantId   = $variantPrice->id ?? null;
        $finalPrice  = 0;
        $warrantyAdjustment = 0;
        $warrantyTierId = null;
        $wholesaleDiscount = 0;
        $basePrice = 0;

        if ($batchWise) {
            // Website is out of stock when no website-enabled batch has stock
            if (!$pricing->isWebsiteSellable($product)) {
                Toastr::error('এই পণ্যটি বর্তমানে স্টক আউট, অর্ডার করা যাবে না।', 'স্টক আউট!');
                return redirect()->back()->withInput();
            }

            // 🎨 Variant eligibility (Spec §18/§26): a specific batch serves ONLY its own
            //    variant — reject combinations with no eligible batch stock (or that don't
            //    match a real variant), instead of silently selling them from the active batch.
            $hasVariants = $product->variantPrices()->exists();
            $pickedColorOrSize = $request->filled('product_color') || $request->filled('product_size');
            if ($hasVariants && $pickedColorOrSize) {
                if (!$variantPrice) {
                    Toastr::error('এই কালার/সাইজ কম্বিনেশনটি বৈধ নয়।', 'ভ্যালিডেশন!');
                    return redirect()->back()->withInput();
                }
                $variantEligibleStock = (int) $pricing->eligibleBatches($product, $variantPrice->id)->sum('remaining_qty');
                if ($variantEligibleStock <= 0) {
                    Toastr::error('এই ভ্যারিয়েন্টটি বর্তমানে স্টক আউট।', 'স্টক আউট!');
                    return redirect()->back()->withInput();
                }
            }

            $finalPrice = $pricing->price($product, null, $variantId, 'website');
            if ($finalPrice <= 0) { $finalPrice = 1; } // safety fallback
            $basePrice = $finalPrice;

            // ⭐ Per-batch billing (Spec §5, config PRICING_MULTI_BATCH_PRICING=per_batch):
            //    charge the quantity-weighted average of the allocated batches so the
            //    line total equals Σ(qty_i × price_i) — never a single active-batch
            //    price applied to the whole quantity. Default stays 'active_batch'.
            if (config('pricing.multi_batch_pricing', 'active_batch') === 'per_batch' && $requestedQty > 0) {
                $weightedUnit = $pricing->weightedAllocationUnit(
                    $product,
                    $requestedQty,
                    $variantId,
                    $pricing->allocationMethod($product)
                );
                if ($weightedUnit > 0) {
                    $finalPrice = $weightedUnit;
                    $basePrice  = $weightedUnit;
                }
            }

            // 🛡️ Warranty — surcharge from the active batch
            if ($request->filled('warranty_tier_id')) {
                $warrantyTier = \App\Models\ProductWarrantyTier::find($request->warranty_tier_id);
                if ($warrantyTier && $warrantyTier->is_active) {
                    $warrantyAdjustment = $pricing->warrantyAdjustment($product, $warrantyTier->id, null, $variantId);
                    $finalPrice += $warrantyAdjustment;
                    $warrantyTierId = $warrantyTier->id;
                }
            }

            // 💰 Wholesale — discount from the active batch. In batch-wise mode the
            //    wholesale tiers BELONG to the batch, so a matching tier always applies
            //    (independent of the legacy products.is_wholesale flag). No tier → 0.
            $cartQty = max(1, (int) ($request->qty ?? 1));
            $wholesaleDiscount = $pricing->wholesale($product, $cartQty, null, $variantId);
            $finalPrice = max(0, $finalPrice - $wholesaleDiscount);
        } else {
            // এখন price ঠিকভাবে fallback করো (legacy)
            if ($variantPrice && $variantPrice->price > 0) {
                $finalPrice = $variantPrice->price;
            } elseif (!empty($product->new_price) && $product->new_price > 0) {
                $finalPrice = $product->new_price;
            } elseif (!empty($product->old_price) && $product->old_price > 0) {
                $finalPrice = $product->old_price;
            } else {
                $finalPrice = 1; // fallback price
            }

            // =========================================================
            // WARRANTY — apply adjustment (legacy)
            // =========================================================
            $warrantyAdjustment = 0;
            $warrantyTierId = null;
            if ($request->filled('warranty_tier_id')) {
                $warrantyTier = \App\Models\ProductWarrantyTier::find($request->warranty_tier_id);
                if ($warrantyTier && $warrantyTier->is_active) {
                    $warrantyAdjustment = (float) ($warrantyTier->additional_cost ?? 0);
                    $finalPrice += $warrantyAdjustment;
                    $warrantyTierId = $warrantyTier->id;
                }
            }

            // =========================================================
            // WHOLESALE PRICING OVERRIDE (legacy)
            // =========================================================
            $wholesaleDiscount = 0;
            $basePrice = $finalPrice; // capture before wholesale deduction
            if ($product->is_wholesale) {
                $cartQty = max(1, (int) ($request->qty ?? 1));
                $vid = $variantPrice->id ?? null;

                // Priority 1: variant-specific tier
                $wholesaleTier = ProductWholesalePrice::where('product_id', $product->id)
                    ->where('variant_id', $vid)
                    ->where('min_quantity', '<=', $cartQty)
                    ->where(function ($q) use ($cartQty) {
                        $q->whereNull('max_quantity')
                          ->orWhere('max_quantity', '>=', $cartQty);
                    })
                    ->orderBy('min_quantity', 'desc')
                    ->first();

                // Priority 2: global tier (variant_id = null)
                if (! $wholesaleTier) {
                    $wholesaleTier = ProductWholesalePrice::where('product_id', $product->id)
                        ->whereNull('variant_id')
                        ->where('min_quantity', '<=', $cartQty)
                        ->where(function ($q) use ($cartQty) {
                            $q->whereNull('max_quantity')
                              ->orWhere('max_quantity', '>=', $cartQty);
                        })
                        ->orderBy('min_quantity', 'desc')
                        ->first();
                }

                if ($wholesaleTier) {
                    $wholesaleDiscount = (float) ($wholesaleTier->wholesale_price ?? 0);
                    $finalPrice = max(0, $finalPrice - $wholesaleDiscount);
                }
            }
        }

        // ⭐ Resolved website batch for this line (used at checkout for FIFO
        //    stock allocation + auto-advance). null in legacy mode.
        $cartBatchId = $batchWise ? ($pricing->activeWebsiteBatch($product)?->id ?? null) : null;

        // সাইজ ও কালারের নাম (চেকআউট ও অর্ডার ডিসপ্লে এর জন্য)
        $sizeName = null;
        $colorName = null;
        if ($request->product_size) {
            $size = Size::find($request->product_size);
            $sizeName = $size ? ($size->sizeName ?? $size->size_name ?? null) : null;
        }
        if ($request->product_color) {
            $color = Color::find($request->product_color);
            $colorName = $color ? ($color->getDisplayName() ?? $color->colorName ?? $color->color_name ?? null) : null;
        }

        // সব ঠিক থাকলে এখন কার্টে Add করো
        Cart::instance('shopping')->add([
            'id'    => $product->id,
            'name'  => $product->name,
            'qty'   => $requestedQty,
            'price' => $finalPrice,
            'options' => [
                'color_id'         => $request->product_color ?? null,
                'size_id'          => $request->product_size ?? null,
                'product_size'     => $sizeName,
                'product_color'    => $colorName,
                'variant_price_id' => $variantPrice->id ?? null,
                'image'            => $product->image->image ?? null,
                'slug'             => $product->slug,
                'purchase_price'   => $product->purchase_price ?? null,

                // 🔥 Advance
                'advance_amount' => (float) ($product->advance_amount ?? 0),

                // 🔥 Digital flag
                'is_digital'     => (int) ($product->is_digital ?? 0),

                // 🔥 Free Delivery flag
                'free_delivery'  => (int) ($product->free_delivery ?? 0),

                // 🏷️ Original prices
                'regular_price'       => $batchWise
                                            ? ($pricing->mrp($product, null, $variantId) ?? 0)
                                            : (float) ($product->old_price ?? 0),
                'sale_price'          => $batchWise ? $basePrice : (float) ($product->new_price ?? 0),
                'base_price'          => $basePrice,

                // ⭐ Batch-wise pricing engine
                'batch_id'            => $cartBatchId,

                // 🛡️ Warranty
                'warranty_tier_id'    => $warrantyTierId,
                'warranty_adjustment' => $warrantyAdjustment,

                // 💰 Wholesale / base price tracking
                'wholesale_discount' => $wholesaleDiscount,
            ],
        ]);

        Toastr::success('Product added to cart successfully', 'Success!');

        // Facebook CAPI AddToCart (server-side)
        try {
            $capiUserData = [
                'client_ip_address' => $request->ip(),
                'client_user_agent' => $request->userAgent(),
            ];
            if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
            if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
            app(\App\Services\FacebookCapiService::class)->sendAddToCart([
                'content_ids'   => [(string) $product->id],
                'content_name'  => strip_tags($product->name),
                'content_type'  => 'product',
                'value'         => $finalPrice * $requestedQty,
                'currency'      => 'BDT',
                'num_items'     => $requestedQty,
            ], $capiUserData, [
                'event_id'        => 'atc_' . $product->id . '_' . time(),
                'event_source_url' => $request->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // non-blocking
        }

        // AJAX/JSON: order_now হলে redirect URL JSON এ পাঠাতে হবে — নইলে ক্লায়েন্ট HTML/302 পেয়ে
        // data.success না মেনে ফর্ম আবার সাবমিট করে কার্টে ডাবল যোগ হয়।
        if ($request->ajax() || $request->wantsJson()) {
            $payload = ['success' => true, 'id' => $product->id];
            if ($request->has('order_now')) {
                $payload['redirect'] = route('customer.checkout');
            }

            return response()->json($payload);
        }

        if ($request->has('order_now')) {
            return redirect()->route('customer.checkout');
        }

        return redirect()->back();
    }

    // ===========================
    // Rest of original controller methods
    // ===========================
	
	
	
	
	
	public function brands()
    {
        $brands = Brand::where('status', 1)
            ->select('id', 'name', 'slug', 'image')
            ->orderBy('name', 'asc')
            ->paginate(24);

        return view('frontEnd.layouts.pages.brands', compact('brands'));
    }

	public function brand($slug, Request $request)
    {
        $brand = Brand::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        $products = Product::where('brand_id', $brand->id)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        // sorting (same pattern as category/shop)
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');

        if ($request->min_price && $request->max_price) {
            $priceColumn = $this->batchPriceSortColumn();
            $products = $products->whereBetween($priceColumn, [
                $request->min_price,
                $request->max_price
            ]);
        }

        $products = $products->paginate(24);

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        return view('frontEnd.layouts.pages.brand', compact(
            'brand',
            'products',
            'min_price',
            'max_price'
        ));
    }

    public function storeIncompleteOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'          => 'nullable|string|max:255',
                'phone'         => 'nullable|string|max:55',
                'address'       => 'nullable|string|max:500',
                'items'         => 'nullable|array',
                'product_image' => 'nullable|string',
                'product_link'  => 'nullable|string',
                'total_amount'  => 'nullable|numeric',
            ]);

            $total = isset($validated['total_amount']) ? floatval($validated['total_amount']) : 0;

            $incomplete = IncompleteOrder::updateOrCreate(
                [
                    'phone'   => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                ],
                [
                    'name'          => $validated['name'] ?? null,
                    'items'         => $validated['items'] ?? [],
                    'product_image' => $validated['product_image'] ?? null,
                    'product_link'  => $validated['product_link'] ?? null,
                    'total_amount'  => $total,
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Incomplete order saved successfully.',
                'data'    => $incomplete
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Incomplete order save failed: '.$e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save incomplete order: '.$e->getMessage()
            ], 500);
        }
    }

    public function hotdeals(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }
        $products = $products->paginate(36);
        app(\App\Services\PricingService::class)->attachCatalogRanges($products);
        return view('frontEnd.layouts.pages.hotdeals', compact('products'));
    }

    public function shop(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock','category_id','brand_id');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        // Filter by category
        if ($request->filled('category')) {
            $products = $products->whereIn('category_id', $request->category);
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $products = $products->whereIn('brand_id', $request->brand);
        }

        // Filter by size
        if ($request->filled('size')) {
            $products = $products->whereHas('prosizes', function ($q) use ($request) {
                $q->whereIn('size_id', $request->size);
            });
        }

        // Filter by color
        if ($request->filled('color')) {
            $products = $products->whereHas('procolors', function ($q) use ($request) {
                $q->whereIn('color_id', $request->color);
            });
        }

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }
        $products = $products->paginate(36);

        // Get filter data
        $categories = Category::where('status', 1)->whereHas('homeproducts', function ($q) {
            $q->where(['status' => 1, 'approval_status' => 'approved']);
        })->select('id', 'name', 'slug')->get();

        $brands = Brand::whereHas('products', function ($q) {
            $q->where(['status' => 1, 'approval_status' => 'approved']);
        })->select('id', 'name', 'slug')->get();

        $sizes = Size::whereHas('productsizes.product', function ($q) {
            $q->where(['status' => 1, 'approval_status' => 'approved']);
        })->select('id', 'sizeName')->get();

        $colors = Color::whereHas('productColors.product', function ($q) {
            $q->where(['status' => 1, 'approval_status' => 'approved']);
        })->select('id', 'colorName', 'color')->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        return view('frontEnd.layouts.pages.shop', compact('products', 'categories', 'brands', 'sizes', 'colors', 'min_price', 'max_price'));
    }




    public function flashsales(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }
        $products = $products->paginate(36);
        app(\App\Services\PricingService::class)->attachCatalogRanges($products);
        return view('frontEnd.layouts.pages.flashsales', compact('products'));
    }

    public function category($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $category = Category::where(['slug' => $slug, 'status' => 1])->firstOrFail();

        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        $subcategories = Subcategory::where('category_id', $category->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $products = $products->whereIn('brand_id', $request->brand);
        }

        // Filter by size
        if ($request->filled('size')) {
            $products = $products->whereHas('prosizes', function ($q) use ($request) {
                $q->whereIn('size_id', $request->size);
            });
        }

        // Filter by color
        if ($request->filled('color')) {
            $products = $products->whereHas('procolors', function ($q) use ($request) {
                $q->whereIn('color_id', $request->color);
            });
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }

        $selectedSubcategories = $request->input('subcategory', []);
        $products = $products->when($selectedSubcategories, function ($query) use ($selectedSubcategories) {
            return $query->whereHas('subcategory', function ($subQuery) use ($selectedSubcategories) {
                $subQuery->whereIn('id', $selectedSubcategories);
            });
        });

        $products = $products->paginate(24);

        // Get filter data for this category
        $brands = Brand::whereHas('products', function ($q) use ($category) {
            $q->where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id]);
        })->select('id', 'name', 'slug')->get();

        $sizes = Size::whereHas('productsizes.product', function ($q) use ($category) {
            $q->where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id]);
        })->select('id', 'sizeName')->get();

        $colors = Color::whereHas('productColors.product', function ($q) use ($category) {
            $q->where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id]);
        })->select('id', 'colorName', 'color')->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        return view('frontEnd.layouts.pages.category', compact('category', 'products', 'subcategories', 'brands', 'sizes', 'colors', 'min_price', 'max_price','soldShow'));
    }

    public function subcategory($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $subcategory = Subcategory::where(['slug' => $slug, 'status' => 1])->firstOrFail();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $subcategory->category_id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        $childcategories = Childcategory::where('subcategory_id', $subcategory->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }

        $selectedChildcategories = $request->input('childcategory', []);
        $products = $products->when($selectedChildcategories, function ($query) use ($selectedChildcategories) {
            return $query->whereHas('childcategory', function ($subQuery) use ($selectedChildcategories) {
                $subQuery->whereIn('id', $selectedChildcategories);
            });
        });

        $products = $products->paginate(24);
        $impproducts = Product::where(['status' => 1, 'topsale' => 1])
            ->with('image')
            ->limit(6)
            ->select('id', 'name', 'slug')
            ->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        return view('frontEnd.layouts.pages.subcategory', compact('subcategory', 'products', 'impproducts', 'childcategories', 'max_price', 'min_price','soldShow'));
    }

    public function products($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $childcategory = Childcategory::where(['slug' => $slug, 'status' => 1])->firstOrFail();
        $childcategories = Childcategory::where('subcategory_id', $childcategory->subcategory_id)->get();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $childcategory->subcategory->category_id ?? 1])->with('category')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'stock');

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $priceColumn = $this->batchPriceSortColumn();
        $min_price = $this->batchCatalogEnabled() ? (float) (clone $products)->min('bm.min_sale') : $products->min('new_price');
        $max_price = $this->batchCatalogEnabled() ? (float) (clone $products)->max('bm.min_sale') : $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where($priceColumn,'>=',$request->min_price);
            $products = $products->where($priceColumn,'<=',$request->max_price);
        }

        $products = $products->paginate(24);
        $impproducts = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->with('image')
            ->limit(6)
            ->select('id', 'name', 'slug','stock')
            ->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        return view('frontEnd.layouts.pages.childcategory', compact('childcategory', 'products', 'impproducts', 'min_price', 'max_price', 'childcategories','soldShow'));
    }

    public function details($slug)
    {
        $cacheKey = 'product_details_' . $slug;
        $details = Cache::remember($cacheKey, 600, function () use ($slug) {
            return Product::where(['slug' => $slug, 'status' => 1, 'approval_status' => 'approved'])
                ->with([
                    'image',
                    'images',
                    'category',
                    'subcategory',
                    'childcategory',
                    'brand',
                    'variantPrices.color',
                    'variantPrices.size',
                    'wholesalePrices'
                ])
                ->firstOrFail();
        });

        // Related products: limit 12, exclude current, eager load to avoid N+1
        $products = Product::where('category_id', $details->category_id)
            ->where('id', '!=', $details->id)
            ->where(['status' => 1, 'approval_status' => 'approved'])
            ->with(['image', 'category', 'brand', 'reviews', 'prosizes', 'procolors'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock', 'category_id', 'brand_id', 'pro_unit')
            ->limit(12)
            ->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        $shippingcharge = Cache::remember('shipping_charges_active', 300, fn() => ShippingCharge::where('status', 1)->get());
        $reviews = Review::where('product_id', $details->id)
            ->where('status', 'active')
            ->latest()
            ->limit(50)
            ->get();

        // Facebook CAPI ViewContent (event_id for Pixel deduplication)
        $vc_event_id = 'vc_prod_' . $details->id . '_' . time();
        try {
            $capiUserData = [
                'client_ip_address' => request()->ip(),
                'client_user_agent' => request()->userAgent(),
            ];
            if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
            if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
            app(\App\Services\FacebookCapiService::class)->sendViewContent([
                'content_name'   => strip_tags($details->name),
                'content_ids'    => [(string) $details->id],
                'content_type'   => 'product',
                'value'          => (float) ($details->new_price ?? $details->old_price ?? 0),
                'currency'       => 'BDT',
                'content_category' => optional($details->category)->name ?? '',
            ], $capiUserData, [
                'event_id'        => $vc_event_id,
                'event_source_url' => request()->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // non-blocking
        }

        // ⭐ Batch-wise pricing engine — resolve variant prices from the active
        //    website batch (base price/stock already come from the accessors).
        $isWebsiteSellable = true;
        $activeBatch = null;
        $variantAvailability = [];
        if (app(\App\Services\PricingService::class)->isBatchWise()) {
            $pricingSvc = app(\App\Services\PricingService::class);
            $isWebsiteSellable = $pricingSvc->isWebsiteSellable($details);
            $activeBatch = $pricingSvc->activeWebsiteBatch($details);

            // Variant selector on the detail page should show batch-variant prices
            $details->variantPrices->each(function ($vp) use ($pricingSvc, $details) {
                $resolved = $pricingSvc->price($details, null, $vp->id, 'website');
                if ($resolved > 0) {
                    $vp->setAttribute('price', $resolved);
                }
            });

            // Attach the storefront price range (price_min/max, mrp_min/max) so the
            // view can render batch-accurate prices, and mirror the resolved default
            // into the legacy new_price/old_price attributes the page + its JS read.
            // (View-only overrides — never persisted — and they also defeat the
            //  10-min `product_details_*` cache staleness when a batch price changes.)
            $pricingSvc->attachCatalogRanges($details);

            $defaultSale = $pricingSvc->price($details, null, null, 'website');
            $defaultMrp  = $pricingSvc->mrp($details, null, null);
            if ($defaultSale > 0) {
                $details->setAttribute('new_price', round($defaultSale, 2));
            }
            if ($defaultMrp !== null && $defaultMrp > 0) {
                $details->setAttribute('old_price', round($defaultMrp, 2));
            }

            // 💰 Batch-wise wholesale: surface the ACTIVE batch's own quantity-discount
            //    tiers on the details page (Spec §9 — wholesale belongs to the batch).
            $wholesaleRows = $activeBatch ? $activeBatch->wholesalePrices()->get() : collect();
            if ($wholesaleRows->isEmpty() && $details->is_wholesale && $details->wholesalePrices->count()) {
                $wholesaleRows = $details->wholesalePrices; // legacy product-level fallback
            }
            if ($wholesaleRows->isNotEmpty()) {
                // Normalize batch rows to the shape the details blade/JS expect.
                $wholesaleRows = $wholesaleRows
                    ->map(function ($w) {
                        $w->setAttribute('variant_id', $w->variant_price_id ?? null);
                        $w->setAttribute('stock', null);
                        return $w;
                    })
                    ->sortBy('min_quantity')
                    ->values();
                $details->setRelation('wholesalePrices', $wholesaleRows);
                if (!$details->is_wholesale) {
                    $details->setAttribute('is_wholesale', true); // view-only, matches batch tier
                }
            }

            // 🎨 Per-variant batch availability (Spec §18/§39): for each combination,
            //    the applicable sale price (min), MRP and total stock across its
            //    eligible batches (specific + ALL fallback). The JS uses this to show
            //    the right price per variant and disable out-of-stock combinations.
            if ($details->variantPrices->count()) {
                foreach ($details->variantPrices as $vp) {
                    $eligible = $pricingSvc->eligibleBatches($details, $vp->id);
                    $range    = $pricingSvc->priceRange($details, $vp->id);
                    $variantAvailability[$vp->id] = [
                        'sale'  => $range['max_sale'] > 0 ? $range['min_sale'] : 0,
                        'mrp'   => $range['max_mrp'] ?? null,
                        'stock' => (int) $eligible->sum('remaining_qty'),
                    ];
                }
            }
        }

        return view('frontEnd.layouts.pages.details', compact(
            'details',
            'products',
            'shippingcharge',
            'reviews',
            'vc_event_id',
            'isWebsiteSellable',
            'activeBatch',
            'variantAvailability'
        ));
    }

    public function quickview(Request $request)
    {
        $data['data'] = Product::where(['id' => $request->id, 'status' => 1, 'approval_status' => 'approved'])
            ->with('images')
            ->withCount('reviews')
            ->first();

        if ($data['data']) {
            app(\App\Services\PricingService::class)->attachCatalogRanges($data['data']);
        }

        $data = view('frontEnd.layouts.ajax.quickview', $data)->render();
        if ($data != '') {
            echo $data;
        }
    }

    public function livesearch(Request $request)
    {
        $keyword = $request->keyword ?? $request->q ?? '';

        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with('image');
        if ($keyword) {
            $products = $products->where('name', 'LIKE', '%' . $keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }
        $products = $products->get();

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        if (empty($request->category) && empty($keyword)) {
            $products = collect();
        }
        return view('frontEnd.layouts.ajax.search', compact('products'));
    }

    public function search(Request $request)
    {
        $keyword = $request->keyword ?? $request->q ?? '';

        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with('image');
        if ($keyword) {
            $products = $products->where('name', 'LIKE', '%' . $keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }

        // ⭐ Batch-wise: sort & filter by the lowest sellable batch price.
        if ($this->batchCatalogEnabled()) {
            $products = $this->joinBatchMinSale($products);
        }

        // sorting
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy($this->batchPriceSortColumn(), 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        // price range filter
        $priceColumn = $this->batchPriceSortColumn();
        if ($request->min_price && $request->max_price) {
            $products = $products->where($priceColumn, '>=', $request->min_price);
            $products = $products->where($priceColumn, '<=', $request->max_price);
        }

        $products = $products->paginate(36);
        $keyword = $request->keyword ?? $request->q ?? '';

        app(\App\Services\PricingService::class)->attachCatalogRanges($products);

        // Facebook CAPI Search (server-side)
        if (!empty($keyword)) {
            try {
                $capiUserData = [
                    'client_ip_address' => request()->ip(),
                    'client_user_agent' => request()->userAgent(),
                ];
                if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
                if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
                app(\App\Services\FacebookCapiService::class)->sendSearch([
                    'search_string' => $keyword,
                ], $capiUserData, [
                    'event_id'        => 'search_' . time(),
                    'event_source_url' => request()->fullUrl(),
                ]);
            } catch (\Throwable $e) {
                // non-blocking
            }
        }

        return view('frontEnd.layouts.pages.search', compact('products', 'keyword'));
    }

    public function shipping_charge(Request $request)
    {
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        
        if ($hasAllFreeDelivery || $request->id == 'free_delivery') {
            Session::put('shipping', 0);
            Session::put('shipping_id', null);
            Session::put('shipping_district', null);
            Session::put('shipping_area_id', null);
        } elseif ($request->type == 'area') {
            // ✅ id = district/area row id → resolve fee via shipping_charge_district pivot
            $area = District::find($request->id);
            $charge = $area ? $area->shippingCharges()->where('status', 1)->first() : null;
            // If the area is NOT linked to a charge, use the admin-created charge
            // the customer picked (radio option). Linked charges always override it.
            $chosen = null;
            if (!$charge && $request->filled('shipping_charge_id')) {
                $chosen = ShippingCharge::where('id', (int) $request->shipping_charge_id)->where('status', 1)->first();
            }
            Session::put('shipping', $charge ? (float) $charge->amount : ($chosen ? (float) $chosen->amount : 0));
            Session::put('shipping_id', $charge ? $charge->id : ($chosen ? $chosen->id : null));
            Session::put('shipping_district', $area ? $area->district : null);
            Session::put('shipping_area_id', $area ? $area->id : null);
        } elseif ($request->id == 'area_clear') {
            Session::put('shipping', 0);
            Session::put('shipping_id', null);
            Session::put('shipping_district', null);
            Session::put('shipping_area_id', null);
        } else {
            // Legacy: id = shipping charge id (campaign page etc.)
            $shipping = ShippingCharge::where(['id' => $request->id])->first();
            if ($shipping) {
                Session::put('shipping', $shipping->amount);
                Session::put('shipping_id', $shipping->id);
            }
        }
        return view('frontEnd.layouts.ajax.cart');
    }

    public function contact()
    {
        $contact = Contact::where('status', 1)->first();
        $cmnmenu = CreatePage::where('status', 1)->get();

        return view('frontEnd.layouts.pages.contact', compact('contact', 'cmnmenu'));
    }

    public function contactStore(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|numeric',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        \App\Models\ContactMessage::create([
            'full_name' => $request->name,
            'mobile'    => $request->phone,
            'email'     => $request->email,
            'subject'   => $request->subject,
            'details'   => $request->message,
            'status'    => 0,
        ]);

        $adminEmail = 'admin@example.com';
        try {
            \Mail::to($adminEmail)->send(new \App\Mail\ContactMail($request->all()));
        } catch (\Exception $e) {
            \Log::error('Email send failed: ' . $e->getMessage());
        }

        Toastr::success('✅ আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে!', 'Success');
        return back();
    }

    public function page($slug)
    {
        $page = CreatePage::where('slug', $slug)->firstOrFail();
        return view('frontEnd.layouts.pages.page', compact('page'));
    }

    public function districts(Request $request)
    {
        $areas = District::where(['district' => $request->id])
            ->orderBy('area_name')
            ->get(['id', 'area_name']);
        return response()->json($areas);
    }

    public function campaign($slug)
    {
        $campaign_data = Campaign::where('slug', $slug)->with('images')->first();

        // Campaign not found -> 404 instead of crashing on ->id
        if (!$campaign_data) {
            abort(404);
        }

        $products = Product::whereIn('id', function($query) use ($campaign_data) {
            $query->select('product_id')
                  ->from('campaign_product')
                  ->where('campaign_id', $campaign_data->id);
        })->orWhere('id', $campaign_data->product_id)
          ->where('status', 1)
          ->where('approval_status', 'approved')
          ->with('image')
          ->get();

        Cart::instance('shopping')->destroy();
        $cart_count = Cart::instance('shopping')->count();
        $product = $products->first();
        if ($cart_count == 0 && $product) {
            Cart::instance('shopping')->add([
                'id'   => $product->id,
                'name' => $product->name,
                'qty'  => 1,
                'price'=> $product->new_price,
                'options' => [
                    'slug'           => $product->slug,
                    'image'          => $product->image->image,
                    'old_price'      => $product->old_price,
                    'purchase_price' => $product->purchase_price,

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
        }

        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $select_charge  = ShippingCharge::where('status', 1)->first();
        if ($select_charge) {
            Session::put('shipping', $select_charge->amount);
        }

        // Facebook CAPI ViewContent — server-side, event_id দিয়ে Pixel-এর সাথে deduplicate হবে
        $fb_view_content_event_id = 'vc_camp' . $campaign_data->id . '_' . time();
        try {
            $capiUserData = [
                'client_ip_address' => request()->ip(),
                'client_user_agent' => request()->userAgent(),
            ];
            if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
            if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
            app(\App\Services\FacebookCapiService::class)->sendViewContent([
                'content_name' => strip_tags($campaign_data->name),
                'content_ids'  => $products->pluck('id')->map(function($id) { return (string)$id; })->values()->toArray(),
                'content_type' => 'product',
                'value'        => (float) (optional($products->first())->new_price ?? 0),
                'currency'     => 'BDT',
                'num_items'    => $products->count(),
            ], $capiUserData, [
                'event_id'        => $fb_view_content_event_id,
                'event_source_url' => request()->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail — page load block করবে না
        }

        // Page builder দিয়ে ডিজাইন করা থাকলে আলাদা ভিউ
        if (!empty($campaign_data->page_html)) {
            return view('frontEnd.layouts.pages.campaign.campaign-builder', compact('campaign_data', 'products', 'shippingcharge', 'fb_view_content_event_id'));
        }

        return view('frontEnd.layouts.pages.campaign.campaign', compact('campaign_data', 'products', 'shippingcharge', 'fb_view_content_event_id'));
    }

    public function payment_success(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController();
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        if ($data[0]->sp_code != 1000) {
            Toastr::error('Your payment failed, try again', 'Oops!');
            return redirect()->route('home');
        }

        if ($data[0]->value1 == 'customer_payment') {
            $customer = Customer::find(Auth::guard('customer')->user()->id);

            $order = new Order();
            $order->invoice_id   = $data[0]->id;
            $order->amount       = $data[0]->amount;
            $order->customer_id  = Auth::guard('customer')->user()->id;
            $order->order_status = 'pending'; // bank_status converted
            $order->save();

            $payment = new Payment();
            $payment->order_id       = $order->id;
            $payment->customer_id    = Auth::guard('customer')->user()->id;
            $payment->payment_method = 'shurjopay';
            $payment->amount         = $order->amount;
            $payment->trx_id         = $data[0]->bank_trx_id;
            $payment->sender_number  = $data[0]->phone_no;
            $payment->payment_status = 'paid';
            $payment->save();

            // Order details + stock update helper
            OrderHelper::saveOrderDetails($order);

            Cart::instance('shopping')->destroy();
            Toastr::success('Thanks, Your payment send successfully', 'Success!');
            return redirect()->route('home');
        }

        Toastr::error('Something wrong, please try again', 'Error!');
        return redirect()->route('home');
    }

    public function payment_cancel(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController();
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        Toastr::error('Your payment cancelled', 'Cancelled!');
        return redirect()->route('home');
    }

    public function offers()
    {
        return view('frontEnd.layouts.pages.offers');
    }

    /**
     * Helper: প্রোডাক্টের stock কলাম থেকে available স্টক বের করবে
     * products টেবিলে stock / qty / quantity – যেটা আছে সেটাই ব্যবহার করবে
     */
    protected function getAvailableStock(Product $product)
    {
        if (isset($product->stock)) {
            return (int) $product->stock;
        }

        if (isset($product->qty)) {
            return (int) $product->qty;
        }

        if (isset($product->quantity)) {
            return (int) $product->quantity;
        }

        // কোনো stock-সংক্রান্ত কলাম না থাকলে null রিটার্ন করবে
        return null;
    }

    // Wholesale Products Page
    public function wholesaleProducts(Request $request)
    {
        $query = Product::where('status', 1)
            ->where('approval_status', 'approved')
            ->where('is_wholesale', 1)
            ->with(['image', 'category', 'brand', 'reviews']);

        // Search
        if ($request->keyword) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        // Category filter
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Sort
        switch ($request->sort) {
            case '2':
                $query->orderBy('id', 'ASC');
                break;
            case '3':
                $query->orderBy('wholesale_price', 'DESC');
                break;
            case '4':
                $query->orderBy('wholesale_price', 'ASC');
                break;
            case '5':
                $query->orderBy('name', 'ASC');
                break;
            case '6':
                $query->orderBy('name', 'DESC');
                break;
            default:
                $query->orderBy('id', 'DESC');
        }

        $products = $query->paginate(24);
        $categories = Category::where('status', 1)->where('parent_id', 0)->get();

        return view('frontEnd.layouts.pages.wholesale_products', compact('products', 'categories'));
    }

    /**
     * 📄 Public invoice download — no auth required (linked from order tracking)
     */
    public function orderInvoice($id)
    {
        $order = \App\Models\Order::where('id', $id)
            ->with(['orderdetails.size', 'orderdetails.color', 'payment', 'shipping'])
            ->firstOrFail();

        $generalsetting = \App\Models\GeneralSetting::where('status', 1)->first();
        $contact = \App\Models\Contact::where('status', 1)->first();

        return view('frontEnd.layouts.customer.invoice', compact('order', 'generalsetting', 'contact'));
    }
}
