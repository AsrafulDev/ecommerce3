@extends('frontEnd.layouts.master')
@section('title', $category->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ route('category', $category->slug) }}" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ $category->meta_description }}" />
    <meta name="keywords" content="{{ $category->slug }}" />

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="{{ $category->name }}" />
    <meta name="twitter:title" content="{{ $category->name }}" />
    <meta name="twitter:description" content="{{ $category->meta_description }}" />
    <meta name="twitter:creator" content="gomobd.com" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta name="twitter:image" content="{{ asset($category->image) }}" />

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $category->name }}" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta property="og:image" content="{{ asset($category->image) }}" />
    <meta property="og:description" content="{{ $category->meta_description }}" />
    <meta property="og:site_name" content="{{ $category->name }}" />
@endpush
@section('content')
    <section class="product-section">
        <div class="container">
            <div class="sorting-section">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="category-breadcrumb d-flex align-items-center">
                            <a href="{{ route('home') }}">{{ __('Home') }}</a>
                            <span>/</span>
                            <strong>{{ $category->name }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="showing-data">
                                    <span>Showing {{ $products->first{{ __('Item') }}() }}-{{ $products->last{{ __('Item') }}() }} of
                                        {{ $products->{{ __('total') }}() }} Results</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="{{ __('filter') }}_sort">
                                    <div class="{{ __('filter') }}_btn">
                                        <i class="fa fa-list-ul"></i>
                                    </div>
                                    <div class="page-sort">
                                        <form action="" class="sort-form">
                                            <select name="sort" class="form-control form-select sort">
                                                <option value="1" @if(request()->get('sort')==1)selected @endif>{{ __('{{ __('Product') }}: {{ __('Late') }}st') }}</option>
                                                <option value="2" @if(request()->get('sort')==2)selected @endif>{{ __('{{ __('Product') }}: Oldest') }}</option>
                                                <option value="3" @if(request()->get('sort')==3)selected @endif>{{ __('Price: High To Low') }}</option>
                                                <option value="4" @if(request()->get('sort')==4)selected @endif>{{ __('Price: Low To High') }}</option>
                                                <option value="5" @if(request()->get('sort')==5)selected @endif>{{ __('{{ __('Name') }}: A-Z') }}</option>
                                                <option value="6" @if(request()->get('sort')==6)selected @endif>{{ __('{{ __('Name') }}: Z-A') }}</option>
                                            </select>
                                            <input type="hidden" name="min_price" value="{{request()->get('min_price')}}" />
                                            <input type="hidden" name="max_price" value="{{request()->get('max_price')}}" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                <div class="col-sm-3 {{ __('filter') }}_sidebar">
                    <div class="{{ __('filter') }}_close"><i class="fa fa-long-arrow-left"></i>{{ __('Filter') }}</div>
                    <form action="" class="attribute-{{ __('submit') }}">
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="category_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseCat" aria-expanded="true" aria-controls="collapseOne">
                                            {{ $category->name }}
                                        </button>
                                    </h2>
                                    <div id="collapseCat" class="accordion-collapse collapse show"
                                        data-bs-parent="#category_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul>
                                                @foreach ($category->subcategories as $key => $subcat)
                                                    <li>
                                                        <a
                                                            href="{{ url('subcategory/' . $subcat->slug) }}">{{ $subcat->subcategory{{ __('Name') }} }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--sidebar item end-->
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="price_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapsePrice" aria-expanded="true" aria-controls="collapseOne">{{ __('Price') }}</button>
                                    </h2>
                                    <div id="collapsePrice" class="accordion-collapse collapse show"
                                        data-bs-parent="#price_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <div class="category-{{ __('filter') }}-box category__wraper" id="categoryFilterBox">
                                                <div class="category-{{ __('filter') }}-item">
                                                    <div class="{{ __('filter') }}-body">
                                                        <div class="slider-box">
                                                            <div class="{{ __('filter') }}-price-inputs">
                                                                <p class="min-price">৳<input type="text"
                                                                        name="min_price" id="min_price" readonly="" />
                                                                </p>
                                                                <p class="max-price">৳<input type="text"
                                                                        name="max_price" id="max_price" readonly="" />
                                                                </p>
                                                            </div>
                                                            <div id="price-range" class="slider form-attribute"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--sidebar item end-->
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="{{ __('filter') }}_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseFilter" aria-expanded="true"
                                            aria-controls="collapseOne">
                                            {{ __('Subcategory') }}
                                        </button>
                                    </h2>
                                    <div id="collapseFilter" class="accordion-collapse collapse show"
                                        data-bs-parent="#{{ __('filter') }}_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <div class="{{ __('filter') }}-body">
                                                <ul class="space-y-3">
                                                    @foreach ($subcategories as $subcategory)
                                                        <li class="subcategory-{{ __('filter') }}-list">
                                                            <label for="{{ $subcategory->slug . '-' . $subcategory->id }}"
                                                                class="subcategory-{{ __('filter') }}-label">
                                                                <input class="form-checkbox form-attribute"
                                                                    id="{{ $subcategory->slug . '-' . $subcategory->id }}"
                                                                    name="subcategory[]" value="{{ $subcategory->id }}"
                                                                    type="checkbox"
                                                                    @if (is_array(request()->get('subcategory')) && in_array($subcategory->id, request()->get('subcategory'))) checked @endif />
                                                                <p class="subcategory-{{ __('filter') }}-name">
                                                                    {{ $subcategory->subcategory{{ __('Name') }} }}</p>
                                                            </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--sidebar item end-->

                        <!-- Brand Filter -->
                        @if(isset($brands) && $brands->count() > 0)
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="brand_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseBrand" aria-expanded="true" aria-controls="collapseOne">{{ __('Brand') }}</button>
                                    </h2>
                                    <div id="collapseBrand" class="accordion-collapse collapse show"
                                        data-bs-parent="#brand_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul class="space-y-3">
                                                @foreach ($brands as $brand)
                                                    <li class="subcategory-{{ __('filter') }}-list">
                                                        <label for="brand-{{ $brand->slug }}-{{ $brand->id }}" class="subcategory-{{ __('filter') }}-label">
                                                            <input class="form-checkbox form-attribute" type="checkbox"
                                                                id="brand-{{ $brand->slug }}-{{ $brand->id }}"
                                                                name="brand[]" value="{{ $brand->id }}"
                                                                @if (is_array(request()->get('brand')) && in_array($brand->id, request()->get('brand'))) checked @endif />
                                                            <p class="subcategory-{{ __('filter') }}-name">{{ $brand->name }}</p>
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <!--sidebar item end-->

                        <!-- Size Filter -->
                        @if(isset($sizes) && $sizes->count() > 0)
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="size_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseSize" aria-expanded="true" aria-controls="collapseOne">{{ __('Size') }}</button>
                                    </h2>
                                    <div id="collapseSize" class="accordion-collapse collapse show"
                                        data-bs-parent="#size_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul class="space-y-3">
                                                @foreach ($sizes as $size)
                                                    <li class="subcategory-{{ __('filter') }}-list">
                                                        <label for="size-{{ $size->id }}" class="subcategory-{{ __('filter') }}-label">
                                                            <input class="form-checkbox form-attribute" type="checkbox"
                                                                id="size-{{ $size->id }}"
                                                                name="size[]" value="{{ $size->id }}"
                                                                @if (is_array(request()->get('size')) && in_array($size->id, request()->get('size'))) checked @endif />
                                                            <p class="subcategory-{{ __('filter') }}-name">{{ $size->size{{ __('Name') }} }}</p>
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <!--sidebar item end-->

                        <!-- {{ __('Color') }} Filter -->
                        @if(isset($colors) && $colors->count() > 0)
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="color_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ __('Color') }}" aria-expanded="true" aria-controls="collapseOne">{{ __('{{ __('Color') }}') }}</button>
                                    </h2>
                                    <div id="collapse{{ __('Color') }}" class="accordion-collapse collapse show"
                                        data-bs-parent="#color_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul class="space-y-3">
                                                @foreach ($colors as $col)
                                                    <li class="subcategory-{{ __('filter') }}-list">
                                                        <label for="color-{{ $col->id }}" class="subcategory-{{ __('filter') }}-label">
                                                            <input class="form-checkbox form-attribute" type="checkbox"
                                                                id="color-{{ $col->id }}"
                                                                name="color[]" value="{{ $col->id }}"
                                                                @if (is_array(request()->get('color')) && in_array($col->id, request()->get('color'))) checked @endif />
                                                            <span class="color-swatch" style="display:inline-block;width:16px;height:16px;background:{{ $col->color }};border:1px solid #ddd;border-radius:3px;vertical-align:middle;margin-right:5px;"></span>
                                                            <p class="subcategory-{{ __('filter') }}-name" style="display:inline;">{{ $col->color{{ __('Name') }} }}</p>
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <!--sidebar item end-->
                    </form>
                </div>
                <div class="col-sm-9">
                    <div class="category-product main_product_inner">
                        @foreach ($products as $key => $value)
                           <div class="product_item wist_item wow zoomIn" data-wow-duration="1.5s"
                            data-wow-delay="0.{{ $key }}s">
                                <div class="product_item_inner">
                                    @if($value->old_price)
                                        <div class="sale-badge">
                                            <div class="sale-badge-inner">
                                                <div class="sale-badge-box">
                                                    <span class="sale-badge-text">
                                                        <p>
                                                            @php
                                                                $discount = (((($value->old_price)-($value->new_price))*100) / ($value->old_price))
                                                            @endphp
                                                            {{ number_format($discount, 0) }}%
                                                        </p>
                                                        ছাড়
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="pro_img">
                                        <a href="{{ route('product', $value->slug) }}">
                                            <img src="{{ asset($value->image ? $value->image->image : '') }}"
                                                alt="{{ $value->name }}" />
                                        </a>
                                    </div>
                                    <div class="pro_des">
                                        <div class="pro_name">
                                            <a href="{{ route('product', $value->slug) }}">
                                                {{ Str::limit($value->name, 35) }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $average{{ __('Rating') }} = $value->reviews->avg('ratting');
                                    $filledStars   = floor($average{{ __('Rating') }});
                                    $hasHalfStar   = $average{{ __('Rating') }} - $filledStars >= 0.5;
                                    $emptyStars    = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                                @endphp

                                @if ($average{{ __('Rating') }} >= 0 && $average{{ __('Rating') }} <= 5)
                                    @for ($i = 0; $i < $filledStars; $i++)
                                        <i class="fas fa-star"></i>
                                    @endfor

                                    @if ($hasHalfStar)
                                        <i class="fas fa-star-half-alt"></i>
                                    @endif

                                    @for ($i = 0; $i < $emptyStars; $i++)
                                        <i class="far fa-star"></i>
                                    @endfor
                                @else
                                    <span>{{ __('{{ __('Inv') }}alid rating range') }}</span>
                                @endif

                                <div class="pro_price">
                                    <p>
                                        <del>৳ {{ $value->old_price }}</del>
                                        ৳ {{ $value->new_price }}
                                    </p>
                                </div>

                                {{-- ✅ এখানে নতুন দুইটা বাটন লাগানো হলো --}}
                                @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                                    {{-- ভ্যারিয়েন্ট আছে → Details পেজে নেবে --}}
                                    <div class="pro_btn">
                                        {{-- বড় অর্ডার বাটন --}}
                                        <a href="{{ route('product', $value->slug) }}"
                                           class="order-btn-link order-btn">
                                            {{ __('Order Now') }}
                                        </a>

                                        {{-- ছোট কার্ট আইকন বাটন --}}
                                        <a href="{{ route('product', $value->slug) }}"
                                           class="cart-icon-link cart-icon-btn">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </a>
                                    </div>
                                @else
                                    {{-- ভ্যারিয়েন্ট নেই → সরাসরি কার্ট + অর্ডার --}}
                                    <div class="pro_btn">

                                        {{-- Order Now --}}
                                        <form action="{{ route('cart.store') }}" method={{ __('"{{ __('POST') }}"') }} class="ajax-cart-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $value->id }}">
                                            <input type="hidden" name="qty" value="1">
                                            <input type="hidden" name="order_now" value="1">
                                            <button type="{{ __('submit') }}" class="order-btn">
                                                {{ __('Order Now') }}
                                            </button>
                                        </form>

                                        {{-- Add to {{ __('Cart') }} --}}
                                        <form action="{{ route('cart.store') }}" method={{ __('"{{ __('POST') }}"') }} class="ajax-cart-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $value->id }}">
                                            <input type="hidden" name="qty" value="1">
                                            <button type="{{ __('submit') }}" class="cart-icon-btn">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </button>
                                        </form>

                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="custom_paginate">
                        {{ $products->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="homeproduct">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="meta_des">
                        {!! $category->meta_description !!}
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
@push('script')
    <script src="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="{{ __('https://') }}ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
    <script>
        $("#price-range").click(function() {
            $(".price-{{ __('submit') }}").{{ __('submit') }}();
        })
        $(".form-attribute").on('change click',function(){
            $(".attribute-{{ __('submit') }}").{{ __('submit') }}();
        })
        $(".sort").change(function() {
            $(".sort-form").{{ __('submit') }}();
        })
        $(".form-checkbox").change(function() {
            $(".subcategory-{{ __('submit') }}").{{ __('submit') }}();
        })
    </script>
    <script>
        $(function() {
            $("#price-range").slider({
                step: 5,
                range: true,
                min: {{ $min_price }},
                max: {{ $max_price }},
                values: [
                    {{ request()->get('min_price') ? request()->get('min_price') : $min_price }},
                    {{ request()->get('max_price') ? request()->get('max_price') : $max_price }}
                ],
                slide: function(event, ui) {
                    $("#min_price").val(ui.values[0]);
                    $("#max_price").val(ui.values[1]);
                }
            });
            $("#min_price").val({{ request()->get('min_price') ? request()->get('min_price') : $min_price }});
            $("#max_price").val({{ request()->get('max_price') ? request()->get('max_price') : $max_price }});
            $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
                1));

            $("#mobile-price-range").slider({
                step: 5,
                range: true,
                min: {{ $min_price }},
                max: {{ $max_price }},
                values: [
                    {{ request()->get('min_price') ? request()->get('min_price') : $min_price }},
                    {{ request()->get('max_price') ? request()->get('max_price') : $max_price }}
                ],
                slide: function(event, ui) {
                    $("#min_price").val(ui.values[0]);
                    $("#max_price").val(ui.values[1]);
                }
            });
            $("#min_price").val({{ request()->get('min_price') ? request()->get('min_price') : $min_price }});
            $("#max_price").val({{ request()->get('max_price') ? request()->get('max_price') : $max_price }});
            $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
                1));

        });
    </script>

    {{-- 🔹 GA4 DataLayer + Facebook Pixel for {{ __('Category') }} Page --}}
    <script type="text/javascript">
        window.dataLayer = window.dataLayer || [];

        (function () {
            var category{{ __('Name') }} = @json($category->name);
            var categorySlug = @json($category->slug);

            var category{{ __('{{ __('Item') }}s') }} = [
                @foreach($products as $index => $value)
                {
                    item_id: "{{ $value->id }}",
                    item_name: @json($value->name),
                    price: {{ (float) $value->new_price }},
                    item_brand: @json(optional($value->brand)->name),
                    item_category: @json(optional($value->category)->name ?? $category->name),
                    item_list_id: categorySlug,
                    item_list_name: category{{ __('Name') }},
                    index: {{ $loop->iteration }},
                    slug: @json($value->slug),
                    currency: "BDT"
                }@if(!$loop->last),@endif
                @endforeach
            ];

            if (category{{ __('{{ __('Item') }}s') }}.length) {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "view_item_list",
                    ecommerce: {
                        item_list_id: categorySlug,
                        item_list_name: category{{ __('Name') }},
                        items: category{{ __('{{ __('Item') }}s') }}.map(function (item) {
                            return {
                                item_id: item.item_id,
                                item_name: item.item_name,
                                index: item.index,
                                price: item.price,
                                item_brand: item.item_brand,
                                item_category: item.item_category,
                                item_list_id: item.item_list_id,
                                item_list_name: item.item_list_name,
                                currency: item.currency
                            };
                        })
                    }
                });
            }

            if (typeof fbq === "function") {
                fbq("trackCustom", "View{{ __('Category') }}", {
                    content_category: category{{ __('Name') }},
                    content_ids: category{{ __('{{ __('Item') }}s') }}.map(function (i) { return i.item_id; }),
                    currency: "BDT"
                });
            }

            function find{{ __('Item') }}ByHref(href) {
                if (!href) return null;
                try {
                    var parts = href.split("/");
                    var last = parts[parts.length - 1].split("?")[0];
                    return category{{ __('{{ __('Item') }}s') }}.find(function (i) { return i.slug === last; }) || null;
                } catch (e) {
                    return null;
                }
            }

            $(document).on("click", ".category-product .product_item a", function () {
                var href = $(this).attr("href") || "";
                var item = find{{ __('Item') }}ByHref(href);
                if (!item) return;

                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "select_item",
                    ecommerce: {
                        item_list_id: categorySlug,
                        item_list_name: category{{ __('Name') }},
                        items: [{
                            item_id: item.item_id,
                            item_name: item.item_name,
                            index: item.index,
                            price: item.price,
                            item_brand: item.item_brand,
                            item_category: item.item_category,
                            item_list_id: item.item_list_id,
                            item_list_name: item.item_list_name,
                            currency: item.currency
                        }]
                    }
                });

                if (typeof fbq === "function") {
                    fbq("trackCustom", "{{ __('Category') }}{{ __('Product') }}Click", {
                        content_ids: [item.item_id],
                        content_name: item.item_name,
                        content_category: item.item_category,
                        value: item.price,
                        currency: "BDT"
                    });
                }
            });

        })();
    </script>
@endpush
