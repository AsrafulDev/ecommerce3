<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\ProductVariantPrice;
use App\Models\ProductWholesalePrice;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Childcategory;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use Toastr;
use File;
use Illuminate\Support\Str;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:product-list|product-create|product-edit|product-delete', ['only' => ['index','show']]);
        $this->middleware('permission:product-create', ['only' => ['create','store']]);
        $this->middleware('permission:product-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }

    /**
     * Whether the products table actually has the `meta_image` column.
     * Cached per request. On live DBs that missed the migration this returns
     * false and the controller simply skips saving meta_image instead of
     * crashing with "Unknown column 'meta_image'".
     */
    protected ?bool $_hasMetaImage = null;

    protected function hasMetaImageColumn(): bool
    {
        if ($this->_hasMetaImage === null) {
            $this->_hasMetaImage = Schema::hasColumn('products', 'meta_image');
        }
        return $this->_hasMetaImage;
    }

    // ================================
    // AJAX: SUBCATEGORY
    // ================================
    public function getSubcategory(Request $request)
    {
        $sub = DB::table("subcategories")
            ->where("category_id", $request->category_id)
            ->pluck('subcategoryName', 'id');

        return response()->json($sub);
    }

    // ================================
    // AJAX: CHILDCATEGORY
    // ================================
    public function getChildcategory(Request $request)
    {
        $child = DB::table("childcategories")
            ->where("subcategory_id", $request->subcategory_id)
            ->pluck('childcategoryName', 'id');

        return response()->json($child);
    }

    // ================================
    // INDEX
    // ================================
    public function index(Request $request)
    {
        $query = Product::orderBy('id','DESC')
            ->with('image','category');

        if ($request->keyword) {
            $query->where('name', 'LIKE', '%' . $request->keyword . "%");
        }

        $data = $query->paginate(10);
        return view('backEnd.product.index', compact('data'));
    }

    // ================================
    // WHOLESALE PRODUCTS
    // ================================
    public function wholesale(Request $request)
    {
        // Show only wholesale products (is_wholesale = 1)
        $query = Product::where('is_wholesale', 1)
            ->orderBy('id','DESC')
            ->with('image','category','wholesalePrices');

        if ($request->keyword) {
            $query->where('name', 'LIKE', '%' . $request->keyword . "%");
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        $data = $query->paginate(20);
        $categories = Category::where('parent_id', 0)->where('status', 1)->select('id', 'name')->get();
        
        return view('backEnd.product.wholesale', compact('data', 'categories'));
    }

    // ================================
    // CREATE
    // ================================
    public function create()
    {
        return view('backEnd.product.create', [
            'categories' => Category::where('parent_id', 0)->where('status', 1)->select('id', 'name')->with('childrenCategories')->get(),
            'brands'     => Brand::where('status', 1)->select('id', 'name')->get(),
            'colors'     => Color::where('status', 1)->get(),
            'sizes'      => Size::where('status', 1)->get(),
        ]);
    }

    // ================================
    // STORE
    // ================================
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'           => 'required',
            'category_id'    => 'required',
            'new_price'      => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock'          => 'nullable|integer|min:0',
            'description'    => 'required',
            'advance_amount' => 'nullable|numeric|min:0',

            'product_type'        => 'required|in:simple,variable,digital',
            'digital_file'        => 'nullable|file|max:51200', // 50MB
            'download_limit'      => 'nullable|integer|min:1',
            'download_expire_days'=> 'nullable|integer|min:1',
            
            // Wholesale fields
            'is_wholesale'        => 'nullable',
            'wholesale_price'    => 'nullable|array',
            'wholesale_price.*.min_quantity' => 'nullable|integer|min:1',
            'wholesale_price.*.max_quantity' => 'nullable|integer|min:1',
            'wholesale_price.*.wholesale_price' => 'nullable|numeric|min:0',

            // 🛡️ Warranty method
            'warranty_method'     => 'nullable|in:active,inactive,hidden',

            // �️ Publish status
            'publish_status'      => 'nullable|in:active,draft,private',

            // �🆕 Barcode & Stock Management
            'barcode'              => 'nullable|string|max:255|unique:products,barcode',
            'barcode_type'         => 'nullable|string|max:10',
            'costing_method'       => 'nullable|in:fifo,lifo,average',
            'low_stock_threshold'  => 'nullable|integer|min:0',
            'allow_negative_stock' => 'nullable',
            'weight'               => 'nullable|string|max:50',
        ]);

        $last_id = Product::max('id') + 1;

        // proSize, proColor, image, meta_image, variant_price, variant_image, digital_file বাদ
        $input = $request->except([
            'image',
            'image_url',
            'meta_image_url',
            'media_image_urls',
            'image_color',
            'image_size',
            'meta_image',
            'variant_price',
            'variant_image',
            'digital_file',
            'proSize',
            'proColor',
            'pro_video_source',
            'pro_video_file',
            'is_digital_check',
        ]);

        foreach ($input as $key => $val) {
            if (is_array($val)) {
                // নেস্টেড অ্যারে হলে implode করবেন না (Array to string conversion এড়াতে)
                $allScalar = !array_filter($val, 'is_array');
                if ($allScalar) {
                    $input[$key] = implode(',', $val);
                } else {
                    unset($input[$key]);
                }
            }
        }

        // PRODUCT TYPE (WooCommerce-like: simple, variable, digital)
        $rawType = $request->product_type;
        $isDigital = $rawType === 'digital' || $request->is_digital == 1;
        $input['is_digital'] = $isDigital ? 1 : 0;
        $input['product_type'] = $isDigital ? 'digital' : ($rawType === 'variable' ? 'variable' : 'simple');

        if ($isDigital) {
            $input['advance_amount'] = 0; // ডিজিটাল হলে advance লাগবে না
        } else {
            $input['advance_amount'] = $request->advance_amount ?? 0;
        }

        // Slug
        $input['slug'] = strtolower(preg_replace('/[\/\s]+/', '-', $request->name.'-'.$last_id));

        // VIDEO — YouTube or local upload
        $this->handleVideoInput($request, $input, null);

        // Price & stock optional – না দিলে ০ ধরা হবে
        $input['new_price']      = $request->filled('new_price') ? $request->new_price : 0;
        $input['purchase_price'] = $request->filled('purchase_price') ? $request->purchase_price : 0;
        // Total stock is maintained via purchase batches / stock adjustments only — never from this form
        $input['stock']          = 0;

        // 🆕 Barcode & Stock Management
        $input['barcode']              = $request->barcode ?: $this->generateUniqueBarcode();
        $input['barcode_type']         = $request->barcode_type ?? 'C128';
        $input['costing_method']       = $request->costing_method ?: 'fifo';
        $input['low_stock_threshold']  = $request->filled('low_stock_threshold') ? (int) $request->low_stock_threshold : 0;
        $input['allow_negative_stock'] = $request->allow_negative_stock ? 1 : 0;
        $input['weight']               = $request->weight;

        // 🏷️ Publish status — new string status, mirrored to legacy boolean
        $publishStatus = in_array($request->publish_status, ['active', 'draft', 'private'])
            ? $request->publish_status
            : Product::STATUS_ACTIVE;

        // Status flags
        $input['publish_status']  = $publishStatus;
        $input['status']          = $publishStatus === Product::STATUS_ACTIVE ? 1 : 0;
        $input['free_delivery']   = $request->free_delivery ? 1 : 0;
        $input['approval_status'] = 'approved'; // Admin created products are auto-approved
        $input['topsale']         = $request->topsale ? 1 : 0;
        $input['feature_product'] = $request->feature_product ? 1 : 0;
        $input['product_code']    = 'P' . str_pad($last_id, 4, '0', STR_PAD_LEFT);
        
        // Wholesale settings
        $input['is_wholesale'] = $request->is_wholesale ? 1 : 0;

        // 🛡️ Warranty method
        $input['warranty_method'] = $request->warranty_method ?? 'active';

        // SEO
        $input['meta_title']       = $request->meta_title ?? $request->name;
        $input['meta_description'] = $request->meta_description ?? Str::limit(strip_tags($request->description), 160);
        $input['meta_keywords']    = $request->meta_keywords ?? '';

        // META IMAGE UPLOAD
        if ($request->hasFile('meta_image')) {
            $metaImg  = $request->file('meta_image');
            $metaName = time().'-meta-'.$metaImg->getClientOriginalName();
            $metaPath = 'public/uploads/product/meta/';
            $metaImg->move($metaPath, $metaName);
            $input['meta_image'] = $metaPath.$metaName;
        } elseif ($request->filled('meta_image_url')) {
            // Selected from Media Gallery (no upload) — relative path
            $input['meta_image'] = $request->input('meta_image_url');
        }

        // DIGITAL FILE UPLOAD
        if ($isDigital) {
            $input['download_limit']       = $request->download_limit ?? 5;
            $input['download_expire_days'] = $request->download_expire_days ?? 7;

            if ($request->hasFile('digital_file')) {
                $file = $request->file('digital_file');
                // storage/app/private/digital-products/...
                $path = $file->store('digital-products', 'private');
                $input['digital_file'] = $path;
            } else {
                $input['digital_file'] = null;
            }
        } else {
            $input['digital_file']        = null;
            $input['download_limit']      = null;
            $input['download_expire_days']= null;
        }

        // CREATE PRODUCT
        // Defensive: if the live DB lacks products.meta_image, drop it from the
        // insert so the query doesn't reference a missing column.
        if (!$this->hasMetaImageColumn()) {
            unset($input['meta_image']);
        }

        $product = Product::create($input);

        log_activity('product', 'create', 'Created product: ' . $product->name, $product, [
            'new_price'      => $product->new_price,
            'purchase_price' => $product->purchase_price,
            'stock'          => $product->stock,
            'product_type'   => $product->product_type,
            'publish_status' => $product->publish_status,
        ]);

        // সাইজ ও কালার অপশনাল – দিলে attach, না দিলে কিছু করব না
        if ($request->proSize && is_array($request->proSize) && count($request->proSize) > 0) {
            $product->sizes()->attach($request->proSize);
        }
        if ($request->proColor && is_array($request->proColor) && count($request->proColor) > 0) {
            $product->colors()->attach($request->proColor);
        }

        // PRODUCT IMAGES (with optional color/size per image)
        if ($request->hasFile('image')) {
            $imageColors = $request->image_color ?? [];
            $imageSizes  = $request->image_size ?? [];
            foreach ($request->file('image') as $idx => $img) {
                $name = time().'-'.$img->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));
                $path = 'public/uploads/product/';
                $img->move($path, $name);

                $colorId = $imageColors[$idx] ?? null;
                $sizeId  = $imageSizes[$idx] ?? null;

                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => $path.$name,
                    'color_id'   => $colorId ?: null,
                    'size_id'    => $sizeId ?: null,
                ]);
            }

            // যদি meta_image সেট করা না থাকে, প্রথম ইমেজকে meta_image করো
            if ($this->hasMetaImageColumn() && empty($product->meta_image) && $product->images()->first()) {
                $product->update(['meta_image' => $product->images()->first()->image]);
            }
        }

        // Image selected from the Media Gallery (no file upload)
        if ($request->filled('image_url')) {
            Productimage::create([
                'product_id' => $product->id,
                'image'      => $request->input('image_url'),
                'color_id'   => null,
                'size_id'    => null,
            ]);
            // Use as meta_image if none set yet
            if ($this->hasMetaImageColumn() && empty($product->meta_image)) {
                $product->update(['meta_image' => $request->input('image_url')]);
            }
        }

        // Multiple images selected from the Media Gallery
        if ($request->filled('media_image_urls')) {
            foreach ((array) $request->input('media_image_urls') as $mediaPath) {
                if (!is_string($mediaPath) || trim($mediaPath) === '') {
                    continue;
                }
                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => trim($mediaPath),
                    'color_id'   => null,
                    'size_id'    => null,
                ]);
            }
            // Use first as meta_image if none set yet
            if ($this->hasMetaImageColumn() && empty($product->meta_image)) {
                $first = $product->images()->first();
                if ($first) {
                    $product->update(['meta_image' => $first->image]);
                }
            }
        }

        // VARIANT PRICES - Single size per variant
        if ($request->variant_price && is_array($request->variant_price)) {
            foreach ($request->variant_price as $variant) {
                $colorId = $variant['color_id'] ?? null;
                $sizeId  = $variant['size_id'] ?? null;
                $price   = $variant['price'] ?? 0;

                // Handle if size_id is accidentally an array
                if (is_array($sizeId)) {
                    $sizeId = !empty($sizeId) ? $sizeId[0] : null;
                }

                if (!empty($colorId) || !empty($sizeId)) {
                    ProductVariantPrice::create([
                        'product_id' => $product->id,
                        'color_id'   => $colorId ?: null,
                        'size_id'    => $sizeId ?: null,
                        'price'      => $price,
                        // Stock is maintained via purchase batches / stock adjustments only
                        'stock'      => 0,
                    ]);
                }
            }
        }

        // VARIANT IMAGES (from Product Variants - variant_image[row_index][image], image_row links to row)
        if ($request->variant_price && is_array($request->variant_price)) {
            $savedFiles = [];
            $doneKeys = [];
            foreach ($request->variant_price as $idx => $vp) {
                $imageRow = $vp['image_row'] ?? $idx;
                $colorId = $vp['color_id'] ?? null;
                $sizeId = $vp['size_id'] ?? null;
                if (is_array($sizeId)) {
                    $sizeId = !empty($sizeId) ? $sizeId[0] : null;
                }
                if (!$colorId) continue;
                $file = $request->file("variant_image.{$imageRow}.image");
                if (!$file) continue;
                $key = $colorId . '_' . ($sizeId ?: '0');
                if (isset($doneKeys[$key])) continue;
                $doneKeys[$key] = true;
                if (!isset($savedFiles[$imageRow])) {
                    $name = time().'-'.uniqid().'-'.$file->getClientOriginalName();
                    $name = strtolower(preg_replace('/\s+/', '-', $name));
                    $path = 'public/uploads/product/';
                    $file->move($path, $name);
                    $savedFiles[$imageRow] = $path.$name;
                }
                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => $savedFiles[$imageRow],
                    'color_id'   => $colorId,
                    'size_id'    => $sizeId ?: null,
                ]);
            }
        }

        // WHOLESALE PRICING TIERS
        if ($input['is_wholesale'] && $request->wholesale_discount && is_array($request->wholesale_discount)) {
            foreach ($request->wholesale_discount as $tier) {
                if (!empty($tier['min_quantity']) && !empty($tier['wholesale_price'])) {
                    ProductWholesalePrice::create([
                        'product_id'      => $product->id,
                        'variant_id'      => $tier['variant_id'] ?? null,
                        'min_quantity'    => $tier['min_quantity'],
                        'max_quantity'    => $tier['max_quantity'] ?? null,
                        'wholesale_price' => $tier['wholesale_price'] ?? 0,
                        // Stock is maintained via purchase batches / stock adjustments only
                        'stock'           => 0,
                    ]);
                }
            }
        }

        Toastr::success('Product created successfully!');
        return redirect()->route('inhouse.products.index');
    }

    // ================================
    // SHOW
    // ================================
    public function show($id)
    {
        $product = Product::with([
            'image',
            'images',
            'category',
            'subcategory',
            'childcategory',
            'brand',
            'colors',
            'sizes',
            'variantPrices',
            'wholesalePrices'
        ])->findOrFail($id);
            
        return view('backEnd.product.show', compact('product'));
    }

    // ================================
    // EDIT
    // ================================
    public function edit($id)
    {
        $edit = Product::with([
            'images.color',
            'images.size',
            'variantPrices',
            // Only stock-IN batches (purchases/adjustments) — 'out' batches are
            // traceability records from sales/replacements and inflate the count.
            'stockBatches' => fn ($q) => $q->where('type', 'in')->orderByDesc('id'),
            'stockBatches.supplier',
            'stockBatches.purchase',
        ])->findOrFail($id);

        return view('backEnd.product.edit', [
            'edit_data'     => $edit,
            'categories'    => Category::where('parent_id', 0)->where('status', 1)->with('childrenCategories')->get(),
            'subcategory'   => Subcategory::where('category_id', $edit->category_id)->get(),
            'childcategory' => Childcategory::where('subcategory_id', $edit->subcategory_id)->get(),
            'brands'        => Brand::where('status', 1)->get(),
            'totalsizes'    => Size::where('status', 1)->get(),
            'totalcolors'   => Color::where('status', 1)->get(),
            'selectcolors'  => Productcolor::where('product_id', $id)->get(),
            'selectsizes'   => Productsize::where('product_id', $id)->get(),
            'wholesalePrices' => \App\Models\ProductWholesalePrice::where('product_id', $id)->get(),
            'warrantyTiers'  => \App\Models\ProductWarrantyTier::where('product_id', $id)->get(),
            'supplierWarranty' => \App\Models\SupplierWarranty::where('product_id', $id)
                ->where('is_transferable', true)->where('warranty_end_date', '>', now())->first(),
        ]);
    }

    // ================================
    // UPDATE
    // ================================
    public function update(Request $request)
    {
        $this->validate($request, [
            'name'           => 'required',
            'category_id'    => 'required',
            'new_price'      => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier_price' => 'nullable|numeric|min:0',
            'stock'          => 'nullable|integer|min:0',
            'description'    => 'required',

            'product_type'        => 'required|in:simple,variable,digital',
            'is_variant'          => 'nullable|boolean',
            'digital_file'        => 'nullable|file|max:51200',
            'download_limit'      => 'nullable|integer|min:1',
            'download_expire_days'=> 'nullable|integer|min:1',
            
            // Wholesale fields
            'is_wholesale'        => 'nullable',
            'wholesale_price'    => 'nullable|array',
            'wholesale_price.*.variant_id'     => 'nullable|integer|exists:product_variant_prices,id',
            'wholesale_price.*.min_quantity' => 'nullable|integer|min:1',
            'wholesale_price.*.max_quantity' => 'nullable|integer|min:1',
            'wholesale_price.*.wholesale_price' => 'nullable|numeric|min:0',

            // 🛡️ Warranty method
            'warranty_method'      => 'nullable|in:active,inactive,hidden',

            // 🛡️ Warranty tiers
            'warranty_tiers'       => 'nullable|array',
            'warranty_tiers.*.variant_id'     => 'nullable|integer',
            'warranty_tiers.*.warranty_type' => 'required_with:warranty_tiers|in:none,supplier_warranty,extended_warranty',
            'warranty_tiers.*.warranty_days' => 'nullable|integer|min:0',
            'warranty_tiers.*.additional_cost' => 'nullable|numeric',
            'warranty_tiers.*.is_active'     => 'nullable',

            // �️ Publish status
            'publish_status'       => 'nullable|in:active,draft,private',

            // �🆕 Barcode & Stock Management
            'barcode'              => 'nullable|string|max:255|unique:products,barcode,' . $request->id,
            'barcode_type'         => 'nullable|string|max:10',
            'costing_method'       => 'nullable|in:fifo,lifo,average',
            'low_stock_threshold'  => 'nullable|integer|min:0',
            'allow_negative_stock' => 'nullable',
            'weight'               => 'nullable|string|max:50',
        ]);

        $product = Product::findOrFail($request->id);

        $input = $request->except([
            'image',
            'image_url',
            'meta_image_url',
            'media_image_urls',
            'image_color',
            'image_size',
            'meta_image',
            'variant_price',
            'variant_image',
            'wholesale_price',
            'warranty_tiers',
            'digital_file',
            'proSize',
            'proColor',
            'pro_video_source',
            'pro_video_file',
            'is_digital_check',
        ]);

        foreach ($input as $key => $val) {
            if (is_array($val)) {
                $allScalar = !array_filter($val, 'is_array');
                if ($allScalar) {
                    $input[$key] = implode(',', $val);
                } else {
                    unset($input[$key]);
                }
            }
        }

        // PRODUCT TYPE (WooCommerce-like: simple, variable, digital)
        $rawType = $request->product_type;
        $isDigital = $rawType === 'digital' || $request->is_digital == 1;
        $input['is_digital'] = $isDigital ? 1 : 0;
        $input['product_type'] = $isDigital ? 'digital' : ($rawType === 'variable' ? 'variable' : 'simple');

        if ($isDigital) {
            $input['advance_amount'] = 0;
        } else {
            $input['advance_amount'] = $request->advance_amount ?? 0;
        }

        // Price & stock optional – আপডেটে না দিলে ০ ধরা হবে
        $input['new_price']      = $request->filled('new_price') ? $request->new_price : 0;
        $input['purchase_price'] = $request->filled('purchase_price') ? $request->purchase_price : 0;
        // Total stock is maintained via purchase batches / stock adjustments only — never from this form
        $input['stock']          = (int) $product->stock;

        // 🆕 Barcode & Stock Management
        $input['barcode']              = $request->barcode ?: $this->generateUniqueBarcode();
        $input['barcode_type']         = $request->barcode_type ?? 'C128';
        $input['costing_method']       = $request->costing_method ?: 'fifo';
        $input['low_stock_threshold']  = $request->filled('low_stock_threshold') ? (int) $request->low_stock_threshold : 0;
        $input['allow_negative_stock'] = $request->allow_negative_stock ? 1 : 0;
        $input['weight']               = $request->weight;

        // Slug & flags
        $input['slug']            = strtolower(preg_replace('/[\/\s]+/', '-', $request->name.'-'.$product->id));

        // 🏷️ Publish status — new string status, mirrored to legacy boolean
        $publishStatus = in_array($request->publish_status, ['active', 'draft', 'private'])
            ? $request->publish_status
            : $product->resolved_publish_status;

        $input['publish_status']  = $publishStatus;
        $input['status']          = $publishStatus === Product::STATUS_ACTIVE ? 1 : 0;
        $input['topsale']         = $request->topsale ? 1 : 0;
        $input['free_delivery']   = $request->free_delivery ? 1 : 0;
        $input['feature_product'] = $request->feature_product ? 1 : 0;

        // VIDEO — YouTube or local upload
        $this->handleVideoInput($request, $input, $product);
        
        // Wholesale settings
        $input['is_wholesale'] = $request->is_wholesale ? 1 : 0;

        // 🛡️ Warranty method
        $input['warranty_method'] = $request->warranty_method ?? 'active';

        // SEO
        $input['meta_title']       = $request->meta_title ?? $request->name;
        $input['meta_description'] = $request->meta_description ?? $request->description;
        $input['meta_keywords']    = $request->meta_keywords ?? '';

        // META IMAGE UPDATE
        if ($request->hasFile('meta_image')) {
            if ($product->meta_image && file_exists($product->meta_image)) {
                @unlink($product->meta_image);
            }
            $metaImg  = $request->file('meta_image');
            $metaName = time().'-meta-'.$metaImg->getClientOriginalName();
            $metaPath = 'public/uploads/product/meta/';
            $metaImg->move($metaPath, $metaName);
            $input['meta_image'] = $metaPath.$metaName;
        } elseif ($request->filled('meta_image_url')) {
            // Selected from Media Gallery (no upload)
            $input['meta_image'] = $request->input('meta_image_url');
        }

        // DIGITAL FILE UPDATE
        if ($isDigital) {
            $input['download_limit']       = $request->download_limit ?? $product->download_limit ?? 5;
            $input['download_expire_days'] = $request->download_expire_days ?? $product->download_expire_days ?? 7;

            if ($request->hasFile('digital_file')) {
                // পুরনো ফাইল ডিলিট
                if ($product->digital_file && Storage::disk('private')->exists($product->digital_file)) {
                    Storage::disk('private')->delete($product->digital_file);
                }

                $file = $request->file('digital_file');
                $path = $file->store('digital-products', 'private');
                $input['digital_file'] = $path;
            } // নতুন ফাইল না দিলে digital_file আগেরটাই থাকবে (update-এ key না পাঠালে unchanged)
        } else {
            // এখন যদি physical করে দাও, তাহলে digital ইনফো ডিলিট
            if ($product->digital_file && Storage::disk('private')->exists($product->digital_file)) {
                Storage::disk('private')->delete($product->digital_file);
            }
            $input['digital_file']        = null;
            $input['download_limit']      = null;
            $input['download_expire_days']= null;
        }

        // PRODUCT UPDATE
        $oldData = [
            'new_price'      => $product->new_price,
            'purchase_price' => $product->purchase_price,
            'stock'          => $product->stock,
            'status'         => $product->status,
            'publish_status' => $product->publish_status,
        ];

        // Defensive: if the live DB lacks products.meta_image, drop it from the
        // update so the query doesn't reference a missing column.
        if (!$this->hasMetaImageColumn()) {
            unset($input['meta_image']);
        }

        $product->update($input);
        Cache::forget('product_details_' . $product->slug);

        $changes = [];
        foreach ($oldData as $k => $old) {
            if ((string) $product->$k !== (string) $old) {
                $changes[$k] = ['old' => $old, 'new' => $product->$k];
            }
        }
        log_activity('product', 'update', 'Updated product: ' . $product->name, $product, $changes);

        // SIZE & COLOR
        $product->sizes()->sync($request->proSize ?? []);
        $product->colors()->sync($request->proColor ?? []);

        // NEW IMAGES (with optional color/size per image)
        if ($request->hasFile('image')) {
            $imageColors = $request->image_color ?? [];
            $imageSizes  = $request->image_size ?? [];
            foreach ($request->file('image') as $idx => $img) {
                $name = time().'-'.$img->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));
                $path = 'public/uploads/product/';
                $img->move($path, $name);

                $colorId = $imageColors[$idx] ?? null;
                $sizeId  = $imageSizes[$idx] ?? null;

                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => $path.$name,
                    'color_id'   => $colorId ?: null,
                    'size_id'    => $sizeId ?: null,
                ]);
            }
        }

        // Image selected from the Media Gallery (no file upload)
        if ($request->filled('image_url')) {
            Productimage::create([
                'product_id' => $product->id,
                'image'      => $request->input('image_url'),
                'color_id'   => null,
                'size_id'    => null,
            ]);
        }

        // Multiple images selected from the Media Gallery
        if ($request->filled('media_image_urls')) {
            foreach ((array) $request->input('media_image_urls') as $mediaPath) {
                if (!is_string($mediaPath) || trim($mediaPath) === '') {
                    continue;
                }
                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => trim($mediaPath),
                    'color_id'   => null,
                    'size_id'    => null,
                ]);
            }
        }

        // VARIANT IMAGES (from Product Variants - variant_image[row][image], image_row links to row)
        if ($request->variant_price && is_array($request->variant_price)) {
            $savedFiles = [];
            $doneKeys = [];
            foreach ($request->variant_price as $idx => $vp) {
                $imageRow = $vp['image_row'] ?? $idx;
                $colorId = $vp['color_id'] ?? null;
                $sizeId = $vp['size_id'] ?? null;
                if (is_array($sizeId)) {
                    $sizeId = !empty($sizeId) ? $sizeId[0] : null;
                }
                if (!$colorId) continue;
                $file = $request->file("variant_image.{$imageRow}.image");
                if (!$file) continue;
                $key = $colorId . '_' . ($sizeId ?: '0');
                if (isset($doneKeys[$key])) continue;
                $doneKeys[$key] = true;
                if (!isset($savedFiles[$imageRow])) {
                    $name = time().'-'.uniqid().'-'.$file->getClientOriginalName();
                    $name = strtolower(preg_replace('/\s+/', '-', $name));
                    $path = 'public/uploads/product/';
                    $file->move($path, $name);
                    $savedFiles[$imageRow] = $path.$name;
                }
                Productimage::create([
                    'product_id' => $product->id,
                    'image'      => $savedFiles[$imageRow],
                    'color_id'   => $colorId,
                    'size_id'    => $sizeId ?: null,
                ]);
            }
        }

        // VARIANTS UPDATE - Single size per variant
        // Preserve existing variant stock (maintained via purchase batches / stock adjustments)
        $existingVariantStock = [];
        ProductVariantPrice::where('product_id', $product->id)
            ->get()
            ->each(function ($vp) use (&$existingVariantStock) {
                $existingVariantStock[($vp->color_id ?: 0) . '_' . ($vp->size_id ?: 0)] = (int) $vp->stock;
            });

        ProductVariantPrice::where('product_id', $product->id)->delete();

        if ($request->variant_price && is_array($request->variant_price)) {
            foreach ($request->variant_price as $variant) {
                $colorId = $variant['color_id'] ?? null;
                $sizeId  = $variant['size_id'] ?? null;
                $price   = $variant['price'] ?? 0;

                // Handle if size_id is accidentally an array
                if (is_array($sizeId)) {
                    $sizeId = !empty($sizeId) ? $sizeId[0] : null;
                }

                if (!empty($colorId) || !empty($sizeId)) {
                    // Stock is maintained via purchase batches / stock adjustments only — preserve existing
                    $stockKey = ($colorId ?: 0) . '_' . ($sizeId ?: 0);
                    $stock    = $existingVariantStock[$stockKey] ?? 0;

                    ProductVariantPrice::create([
                        'product_id' => $product->id,
                        'color_id'   => $colorId ?: null,
                        'size_id'    => $sizeId ?: null,
                        'price'      => $price,
                        'stock'      => $stock,
                    ]);
                }
            }
        }

        // WHOLESALE PRICING TIERS UPDATE
        // Preserve existing wholesale stock (maintained via purchase batches / stock adjustments)
        $existingWholesaleStock = [];
        ProductWholesalePrice::where('product_id', $product->id)
            ->get()
            ->each(function ($tier) use (&$existingWholesaleStock) {
                $existingWholesaleStock[($tier->variant_id ?: 0) . '_' . ($tier->min_quantity ?: 0)] = (int) $tier->stock;
            });

        ProductWholesalePrice::where('product_id', $product->id)->delete();

        if ($input['is_wholesale'] && $request->wholesale_discount && is_array($request->wholesale_discount)) {
            foreach ($request->wholesale_discount as $tier) {
                if (!empty($tier['min_quantity']) && !empty($tier['wholesale_price'])) {
                    $stockKey = (($tier['variant_id'] ?? null) ?: 0) . '_' . ($tier['min_quantity'] ?: 0);
                    ProductWholesalePrice::create([
                        'product_id'      => $product->id,
                        'variant_id'      => $tier['variant_id'] ?? null,
                        'min_quantity'    => $tier['min_quantity'],
                        'max_quantity'    => $tier['max_quantity'] ?? null,
                        'wholesale_price' => $tier['wholesale_price'] ?? 0,
                        'stock'           => $existingWholesaleStock[$stockKey] ?? 0,
                    ]);
                }
            }
        }

        // 🛡️ WARRANTY TIERS — delete all and recreate
        \App\Models\ProductWarrantyTier::where('product_id', $product->id)->delete();
        if ($request->warranty_tiers && is_array($request->warranty_tiers)) {
            foreach ($request->warranty_tiers as $i => $tier) {
                if (!empty($tier['warranty_type'])) {
                    $days = (int) ($tier['warranty_days'] ?? 0);
                    $type = $tier['warranty_type'];
                    $tierName = match ($type) {
                        'none'              => 'No Warranty',
                        'supplier_warranty' => $days > 0 ? "Supplier Warranty ({$days} Days)" : 'Supplier Warranty',
                        'extended_warranty' => $days > 0 ? "Extended Warranty ({$days} Days)" : 'Extended Warranty',
                        default             => 'Warranty',
                    };
                    \App\Models\ProductWarrantyTier::create([
                        'product_id'      => $product->id,
                        'variant_id'      => $tier['variant_id'] ?? null,
                        'tier_name'       => $tierName,
                        'warranty_type'   => $type,
                        'warranty_days'   => $days,
                        'additional_cost' => $tier['additional_cost'] ?? 0,
                        'is_active'       => ($tier['is_active'] ?? '1') == '1',
                        'sort_order'      => $i,
                    ]);
                }
            }
        }

        Toastr::success('Product updated successfully!');
        return redirect()->route('inhouse.products.index');
    }

    // ================================
    // DELETE / IMAGE DELETE
    // ================================
    public function destroy(Request $request)
    {
        $product = Product::findOrFail($request->hidden_id);

        // digital ফাইল থাকলে ডিলিট
        if ($product->digital_file && Storage::disk('private')->exists($product->digital_file)) {
            Storage::disk('private')->delete($product->digital_file);
        }

        // uploaded video থাকলে ডিলিট
        if ($product->pro_video_path && file_exists($product->pro_video_path)) {
            @unlink($product->pro_video_path);
        }

        $product->delete();
        Toastr::success('Product deleted successfully');
        return redirect()->back();
    }

    public function imgdestroy(Request $request)
    {
        $img = Productimage::findOrFail($request->id);
        $imagePath = $img->image;
        $productId = $img->product_id;

        // Delete all Productimage rows with same image path (variant images saved per size share same path)
        $allSame = Productimage::where('product_id', $productId)->where('image', $imagePath)->get();

        // Try delete from public disk if stored via storage/app/public/...
        $possiblePublicPath = str_replace('storage/', '', $imagePath); // if DB stores 'storage/uploads/...'
        if ($possiblePublicPath && Storage::disk('public')->exists($possiblePublicPath)) {
            Storage::disk('public')->delete($possiblePublicPath);
        } else {
            // fallback: if absolute or relative path present on filesystem
            if (file_exists(public_path($imagePath))) {
                @unlink(public_path($imagePath));
            } elseif (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        foreach ($allSame as $m) {
            $m->delete();
        }
        $product = Product::find($productId);
        if ($product) {
            Cache::forget('product_details_' . $product->slug);
        }

        Toastr::success('Image deleted successfully!');
        return redirect()->back();
    }

    // ================================
    // BULK ACTIONS (AJAX / POST)
    // ================================
    public function update_deals(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'status' => 'required|in:0,1',
        ]);

        Product::whereIn('id', $request->product_ids)->update(['topsale' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Products updated successfully']);
    }

    public function update_status(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'status' => 'required|in:0,1',
        ]);

        // Keep publish_status in sync so the boolean toggle also updates visibility:
        // on → active (visible everywhere), off → draft (hidden everywhere, incl. POS)
        Product::whereIn('id', $request->product_ids)->update([
            'status'         => $request->status,
            'publish_status' => $request->status == 1 ? Product::STATUS_ACTIVE : Product::STATUS_DRAFT,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Products status updated']);
    }

    // ================================
    // SINGLE PRODUCT ACTIVATE / DEACTIVATE
    // ================================
    public function inactive(Request $request)
    {
        $product = Product::find($request->hidden_id);
        if ($product) {
            // Deactivate → hidden everywhere (incl. POS)
            $product->update([
                'status'         => 0,
                'publish_status' => Product::STATUS_DRAFT,
            ]);
            Cache::forget('product_details_' . $product->slug);
            Cache::forget('frontend_homepage_v1');
            Toastr::success('Product deactivated successfully', 'Success');
        }
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $product = Product::find($request->hidden_id);
        if ($product) {
            // Activate → visible everywhere
            $product->update([
                'status'         => 1,
                'publish_status' => Product::STATUS_ACTIVE,
            ]);
            Cache::forget('product_details_' . $product->slug);
            Cache::forget('frontend_homepage_v1');
            Toastr::success('Product activated successfully', 'Success');
        }
        return redirect()->back();
    }

    // ================================
    // PENDING PRODUCTS (FOR APPROVAL)
    // ================================
    public function pending(Request $request)
    {
        $query = Product::where('approval_status', 'pending')
            ->orderBy('id','DESC')
            ->with('image','category');

        if ($request->keyword) {
            $query->where('name', 'LIKE', '%' . $request->keyword . "%");
        }

        $data = $query->paginate(10);
        return view('backEnd.product.pending', compact('data'));
    }

    // ================================
    // APPROVE PRODUCT
    // ================================
    public function approve(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->approval_status = 'approved';
        $product->save();

        Toastr::success('Product approved successfully!');
        return redirect()->back();
    }

    // ================================
    // REJECT PRODUCT
    // ================================
    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($request->id);
        $product->approval_status = 'rejected';
        $product->save();

        // Store rejection reason if provided (you can add a rejection_reason column later)
        // $product->rejection_reason = $request->rejection_reason;
        // $product->save();

        Toastr::success('Product rejected successfully!');
        return redirect()->back();
    }

    // ================================
    // VIDEO HELPERS
    // ================================

    /**
     * Handle pro_video + pro_video_type + pro_video_path for store/update.
     * $product = null on create, Product instance on update.
     */
    private function handleVideoInput(Request $request, array &$input, $product): void
    {
        $videoType = $request->input('pro_video_source', 'youtube'); // 'youtube' or 'upload'

        if ($videoType === 'upload') {
            if ($request->hasFile('pro_video_file')) {
                // Delete old uploaded video if exists
                if ($product && $product->pro_video_path && file_exists($product->pro_video_path)) {
                    @unlink($product->pro_video_path);
                }

                $file      = $request->file('pro_video_file');
                $ext       = $file->getClientOriginalExtension();
                $fileName  = time() . '-video.' . $ext;
                $dir       = 'public/uploads/product/videos/';

                // Image pattern অনুসরণ করে — CWD (htdocs) relative path
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $file->move($dir, $fileName);

                $input['pro_video']      = null;
                $input['pro_video_type'] = 'upload';
                $input['pro_video_path'] = $dir . $fileName;
            } else {
                // No new file — keep existing if update, else clear
                if ($product) {
                    unset($input['pro_video'], $input['pro_video_type'], $input['pro_video_path']);
                } else {
                    $input['pro_video']      = null;
                    $input['pro_video_type'] = null;
                    $input['pro_video_path'] = null;
                }
            }
        } else {
            // YouTube mode
            $ytId = $this->getYouTubeVideoId($request->input('pro_video'));

            // Delete old uploaded video if switching from upload to YouTube
            if ($product && $product->pro_video_path && file_exists($product->pro_video_path)) {
                @unlink($product->pro_video_path);
            }

            $input['pro_video']      = $ytId;
            $input['pro_video_type'] = $ytId ? 'youtube' : null;
            $input['pro_video_path'] = null;
        }
    }

    private function getYouTubeVideoId($input)
    {
        if (!$input) return null;

        // শুধু ১১ ক্যারেক্টারের ID হলে
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }

        // পূর্ণ URL হলে
        preg_match(
            '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $input,
            $matches
        );

        return $matches[1] ?? null;
    }

    /**
     * Auto-generate a unique 6-digit barcode when none is provided.
     * Range: 100000 – 999999 (always exactly 6 digits, no leading zeros).
     */
    private function generateUniqueBarcode(): string
    {
        $barcode = (string) random_int(100000, 999999);
        $attempts = 0;

        // Re-roll on collision (up to 50 attempts, then bump to the next free number)
        while (Product::where('barcode', $barcode)->exists()) {
            if (++$attempts >= 50) {
                $max = (int) Product::where('barcode', 'regexp', '^[0-9]{6}$')->max('barcode');
                $barcode = (string) max($max + 1, 100000);
                break;
            }
            $barcode = (string) random_int(100000, 999999);
        }

        return $barcode;
    }
}
