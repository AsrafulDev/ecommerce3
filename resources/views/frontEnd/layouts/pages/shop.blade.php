@extends('frontEnd.layouts.master')
@section('title', '{{ __('Shop') }}')
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
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
                            <strong>{{ __('Shop') }}</strong>
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
                        <!-- {{ __('Category') }} Filter -->
                        @if(isset($categories) && $categories->count() > 0)
                        <div class="sidebar_item wraper__item">
                            <div class="accordion" id="category_sidebar">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ __('Category') }}" aria-expanded="true" aria-controls="collapseOne">{{ __('{{ __('Category') }}') }}</button>
                                    </h2>
                                    <div id="collapse{{ __('Category') }}" class="accordion-collapse collapse show"
                                        data-bs-parent="#category_sidebar">
                                        <div class="accordion-body cust_according_body">
                                            <ul class="space-y-3">
                                                @foreach ($categories as $cat)
                                                    <li class="subcategory-{{ __('filter') }}-list">
                                                        <label for="cat-{{ $cat->slug }}-{{ $cat->id }}" class="subcategory-{{ __('filter') }}-label">
                                                            <input class="form-checkbox form-attribute" type="checkbox"
                                                                id="cat-{{ $cat->slug }}-{{ $cat->id }}"
                                                                name="category[]" value="{{ $cat->id }}"
                                                                @if (is_array(request()->get('category')) && in_array($cat->id, request()->get('category'))) checked @endif />
                                                            <p class="subcategory-{{ __('filter') }}-name">{{ $cat->name }}</p>
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

                        <!-- Price Filter -->
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

                                @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                                    <div class="pro_btn">
                                        <a href="{{ route('product', $value->slug) }}"
                                           class="order-btn-link order-btn">
                                            {{ __('Order Now') }}
                                        </a>
                                        <a href="{{ route('product', $value->slug) }}"
                                           class="cart-icon-link cart-icon-btn">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </a>
                                    </div>
                                @else
                                    <div class="pro_btn">
                                        <form action="{{ route('cart.store') }}" method={{ __('"{{ __('POST') }}"') }} class="ajax-cart-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $value->id }}">
                                            <input type="hidden" name="qty" value="1">
                                            <input type="hidden" name="order_now" value="1">
                                            <button type="{{ __('submit') }}" class="order-btn">
                                                {{ __('Order Now') }}
                                            </button>
                                        </form>
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
@endsection

@push('script')
<script src="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="{{ __('https://') }}ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script>
    $(".sort").change(function(){
       $('#loading').show();
       $(".sort-form").{{ __('submit') }}();
    })
    $(".form-attribute").on('change click',function(){
        $(".attribute-{{ __('submit') }}").{{ __('submit') }}();
    })
    $(function() {
        $("#price-range").slider({
            step: 5,
            range: true,
            min: {{ $min_price ?? 0 }},
            max: {{ $max_price ?? 1000 }},
            values: [
                {{ request()->get('min_price') ? request()->get('min_price') : ($min_price ?? 0) }},
                {{ request()->get('max_price') ? request()->get('max_price') : ($max_price ?? 1000) }}
            ],
            slide: function(event, ui) {
                $("#min_price").val(ui.values[0]);
                $("#max_price").val(ui.values[1]);
            }
        });
        $("#min_price").val({{ request()->get('min_price') ? request()->get('min_price') : ($min_price ?? 0) }});
        $("#max_price").val({{ request()->get('max_price') ? request()->get('max_price') : ($max_price ?? 1000) }});

        @if(isset($min_price) && isset($max_price))
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
        @endif
    });
</script>
@endpush
