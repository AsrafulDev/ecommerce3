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
use App\Models\Vendor;
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
            ->select('id', 'name', 'slug', 'icon', 'image')
            ->with(['subcategories.childcategories'])
            ->orderBy('id', 'ASC')
            ->get();

        // Front categories (যদি অন্য কোথাও ব্যবহার হয়)
        $frontcategory = Category::where(['status' => 1])
            ->select('id', 'name', 'image', 'icon', 'slug', 'status')
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
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'stock')
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
            $homeproducts = Category::where(['front_view' => 1, 'status' => 1])
                ->orderBy('id', 'ASC')
                ->with([
                    'products' => function ($q) {
                        $q->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id')
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
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'stock')
                ->with(['prosizes', 'procolors', 'image', 'reviews'])
                ->limit(14)
                ->get();
        } else {
            $all_products = null;
        }

        // Active Vendors with shop info - for shop cards display
        $vendors = Vendor::where('status', 1)
            ->where('verification_status', 'approved')
            ->select('id', 'shop_name', 'slug', 'logo', 'banner', 'status')
            ->withCount(['products' => function($query) {
                $query->where('status', 1)->where('approval_status', 'approved');
            }])
            ->having('products_count', '>', 0) // Only show vendors with at least one approved product
            ->orderBy('id', 'DESC')
            ->limit(12)
            ->get();

        // ✅ Performance: Single query instead of N+1 for vendor review stats
        $vendorIds = $vendors->pluck('id')->toArray();
        $vendorReviewStats = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->whereIn('products.vendor_id', $vendorIds)
            ->where('products.status', 1)
            ->where('products.approval_status', 'approved')
            ->where('reviews.status', 'active')
            ->selectRaw('products.vendor_id, COUNT(*) as total_reviews, AVG(reviews.ratting) as avg_rating')
            ->groupBy('products.vendor_id')
            ->get()
            ->keyBy('vendor_id');

        foreach ($vendors as $vendor) {
            $stats = $vendorReviewStats->get($vendor->id);
            $vendor->total_reviews = $stats ? (int) $stats->total_reviews : 0;
            $vendor->average_rating = $stats && $stats->total_reviews > 0 ? round((float) $stats->avg_rating, 1) : 0;
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
            'vendors'
        );
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

        // এখন price ঠিকভাবে fallback করো
        if ($variantPrice && $variantPrice->price > 0) {
            $finalPrice = $variantPrice->price;
        } elseif (!empty($product->new_price) && $product->new_price > 0) {
            $finalPrice = $product->new_price;
        } elseif (!empty($product->old_price) && $product->old_price > 0) {
            $finalPrice = $product->old_price;
        } else {
            $finalPrice = 1; // fallback price
        }

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
	
	
	
	
	
	public function brand($slug, Request $request)
{
    $brand = Brand::where('slug', $slug)
        ->where('status', 1)
        ->firstOrFail();

    $products = Product::where('brand_id', $brand->id)
        ->where('status', 1)
        ->where('approval_status', 'approved')
        ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock');

    // sorting (same pattern as category/shop)
    if ($request->sort == 1) {
        $products = $products->orderBy('created_at', 'desc');
    } elseif ($request->sort == 2) {
        $products = $products->orderBy('created_at', 'asc');
    } elseif ($request->sort == 3) {
        $products = $products->orderBy('new_price', 'desc');
    } elseif ($request->sort == 4) {
        $products = $products->orderBy('new_price', 'asc');
    } elseif ($request->sort == 5) {
        $products = $products->orderBy('name', 'asc');
    } elseif ($request->sort == 6) {
        $products = $products->orderBy('name', 'desc');
    } else {
        $products = $products->latest();
    }

    $min_price = $products->min('new_price');
    $max_price = $products->max('new_price');

    if ($request->min_price && $request->max_price) {
        $products = $products->whereBetween('new_price', [
            $request->min_price,
            $request->max_price
        ]);
    }

    $products = $products->paginate(24);

    return view('frontEnd.layouts.pages.brand', compact(
        'brand',
        'products',
        'min_price',
        'max_price'
    ));
}

    public function vendorShop($slug, Request $request)
    {
        $generalSetting = GeneralSetting::where('status', 1)->first();
        if (!$generalSetting || ($generalSetting->vendor_enabled ?? 1) != 1) {
            abort(404);
        }

        $vendor = Vendor::where('slug', $slug)
            ->where('status', 1)
            ->where('verification_status', 'approved')
            ->firstOrFail();

        // Get vendor products
        $products = Product::where('vendor_id', $vendor->id)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock', 'sold')
            ->with(['image', 'reviews', 'prosizes', 'procolors']);

        // Sorting
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } else {
            $products = $products->latest();
        }

        $products = $products->paginate(24);

        // Calculate vendor stats
        $vendorProducts = Product::where('vendor_id', $vendor->id)
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->pluck('id');
        
        $reviews = Review::whereIn('product_id', $vendorProducts)
            ->where('status', 'active')
            ->get();
        
        $vendor->total_reviews = $reviews->count();
        $vendor->average_rating = $reviews->count() > 0 
            ? round($reviews->avg('ratting'), 1) 
            : 0;
        $vendor->total_products = $vendorProducts->count();

        // General setting
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();
        $seo = DB::table('seo_settings')->first();

        return view('frontEnd.layouts.pages.vendor-shop', compact(
            'vendor',
            'products',
            'generalsetting',
            'seo'
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

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.hotdeals', compact('products'));
    }

    public function sellers(Request $request)
    {
        $generalSetting = GeneralSetting::where('status', 1)->first();
        if (!$generalSetting || ($generalSetting->vendor_enabled ?? 1) != 1) {
            abort(404);
        }

        // Get all active and verified vendors
        $vendors = Vendor::where('status', 1)
            ->where('verification_status', 'approved')
            ->select('id', 'shop_name', 'slug', 'logo', 'banner', 'status', 'verification_status')
            ->withCount(['products' => function($query) {
                $query->where('status', 1)->where('approval_status', 'approved');
            }])
            ->having('products_count', '>', 0) // Only show vendors with at least one approved product
            ->orderBy('id', 'DESC');

        // Search functionality
        if ($request->keyword) {
            $vendors->where('shop_name', 'like', '%' . $request->keyword . '%');
        }

        $vendors = $vendors->paginate(24);

        // Calculate average rating for each vendor
        foreach ($vendors as $vendor) {
            $vendorProducts = Product::where('vendor_id', $vendor->id)
                ->where('status', 1)
                ->where('approval_status', 'approved')
                ->pluck('id');
            
            $reviews = Review::whereIn('product_id', $vendorProducts)
                ->where('status', 'active')
                ->get();
            
            $vendor->total_reviews = $reviews->count();
            $vendor->average_rating = $reviews->count() > 0 
                ? round($reviews->avg('ratting'), 1) 
                : 0;
        }

        // General setting
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();
        $seo = DB::table('seo_settings')->first();

        return view('frontEnd.layouts.pages.sellers', compact(
            'vendors',
            'generalsetting',
            'seo'
        ));
    }

    public function shop(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved'])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock');

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.shop', compact('products'));
    }




    public function flashsales(Request $request)
    {
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price','stock');

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }
        $products = $products->paginate(36);
        return view('frontEnd.layouts.pages.flashsales', compact('products'));
    }

    public function category($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $category = Category::where(['slug' => $slug, 'status' => 1])->first();

        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'category_id' => $category->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id','sold','stock');
        $subcategories = Subcategory::where('category_id', $category->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }

        $selectedSubcategories = $request->input('subcategory', []);
        $products = $products->when($selectedSubcategories, function ($query) use ($selectedSubcategories) {
            return $query->whereHas('subcategory', function ($subQuery) use ($selectedSubcategories) {
                $subQuery->whereIn('id', $selectedSubcategories);
            });
        });

        $products = $products->paginate(24);
        return view('frontEnd.layouts.pages.category', compact('category', 'products', 'subcategories', 'min_price', 'max_price','soldShow'));
    }

    public function subcategory($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $subcategory = Subcategory::where(['slug' => $slug, 'status' => 1])->first();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'subcategory_id' => $subcategory->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id','sold','stock');
        $childcategories = Childcategory::where('subcategory_id', $subcategory->id)->get();

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
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

        return view('frontEnd.layouts.pages.subcategory', compact('subcategory', 'products', 'impproducts', 'childcategories', 'max_price', 'min_price','soldShow'));
    }

    public function products($slug, Request $request)
    {
        $soldShow = $request->sold=='show'?true:false;
        $childcategory = Childcategory::where(['slug' => $slug, 'status' => 1])->first();
        $childcategories = Childcategory::where('subcategory_id', $childcategory->subcategory_id)->get();
        $products = Product::where(['status' => 1, 'approval_status' => 'approved', 'childcategory_id' => $childcategory->id])->with('category')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id', 'childcategory_id','sold','stock');

        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if($request->min_price && $request->max_price){
            $products = $products->where('new_price','>=',$request->min_price);
            $products = $products->where('new_price','<=',$request->max_price);
        }

        $products = $products->paginate(24);
        $impproducts = Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->with('image')
            ->limit(6)
            ->select('id', 'name', 'slug','stock')
            ->get();

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

        return view('frontEnd.layouts.pages.details', compact(
            'details',
            'products',
            'shippingcharge',
            'reviews',
            'vc_event_id'
        ));
    }

    public function quickview(Request $request)
    {
        $data['data'] = Product::where(['id' => $request->id, 'status' => 1, 'approval_status' => 'approved'])
            ->with('images')
            ->withCount('reviews')
            ->first();

        $data = view('frontEnd.layouts.ajax.quickview', $data)->render();
        if ($data != '') {
            echo $data;
        }
    }

    public function livesearch(Request $request)
    {
        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with('image');
        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }
        $products = $products->get();

        if (empty($request->category) && empty($request->keyword)) {
            $products = [];
        }
        return view('frontEnd.layouts.ajax.search', compact('products'));
    }

    public function search(Request $request)
    {
        $products = Product::select('id', 'name', 'slug', 'new_price', 'old_price','stock')
            ->where('status', 1)
            ->where('approval_status', 'approved')
            ->with('image');
        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category) {
            $products = $products->where('category_id', $request->category);
        }
        $products = $products->paginate(36);
        $keyword = $request->keyword ?? '';

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
        } else {
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

        \App\Models\Contact::create([
            'name'    => $request->name,
            'mobile'  => $request->phone,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
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
        $areas = District::where(['district' => $request->id])->pluck('area_name', 'id');
        return response()->json($areas);
    }

    public function campaign($slug)
    {
        $campaign_data = Campaign::where('slug', $slug)->with('images')->first();

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
            $order->order_status = $data[0]->bank_status;
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
}
