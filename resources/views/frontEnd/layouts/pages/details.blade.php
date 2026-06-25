@extends('frontEnd.layouts.master')
@section('title', $details->name)
@php
    $pageType = 'product';
@endphp
@push('dataLayer')
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: 'view_item',
    ecommerce: {
        currency: 'BDT',
        value: {{ $details->new_price ?? 0 }},
        items: [{
            item_id: '{{ $details->id }}',
            item_name: '{{ addslashes($details->name) }}',
            price: {{ $details->new_price ?? 0 }},
            item_category: '{{ addslashes(optional($details->category)->name ?? '') }}',
            quantity: 1
        }]
    }
});
if (typeof fbq === 'function') {
    fbq('track', 'ViewContent', {
        content_ids: ['{{ $details->id }}'],
        content_name: '{{ addslashes($details->name) }}',
        content_type: 'product',
        value: {{ $details->new_price ?? 0 }},
        currency: 'BDT'
    }, { eventID: '{{ $vc_event_id ?? "vc_prod_".$details->id."_".time() }}' });
}
if (typeof ttq !== 'undefined') {
    ttq.track('ViewContent', {
        content_type: 'product',
        content_id: '{{ $details->id }}',
        content_name: '{{ addslashes($details->name) }}',
        value: {{ $details->new_price ?? 0 }},
        currency: 'BDT'
    });
}
@endpush
@push('seo')
@php
    $metaTitle = $details->meta_title ?? $details->name;
    $metaDescription = $details->meta_description ?? Str::limit(strip_tags($details->description), 160);
    $metaKeywords = $details->meta_keywords ?? $details->name;
    $metaImage = $details->meta_image ? asset($details->meta_image) : asset(optional($details->image)->image);
@endphp

<meta name="app-url" content="{{ route('product', $details->slug) }}" />
<meta name="robots" content="index, follow" />

<meta name="title" content="{{ $metaTitle }}" />
<meta name="description" content="{{ $metaDescription }}" />
<meta name="keywords" content="{{ $metaKeywords }}" />

<!-- Twitter Card data -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="@gomobd" />
<meta name="twitter:title" content="{{ $metaTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $metaImage }}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta property="og:image" content="{{ $metaImage }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:site_name" content="gomobd.com" />
@endpush


@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/zoomsl.css') }}">
<style>
/* ✅ Scoped Review Section */
.gomobd-review-section {
    font-family: 'Poppins', sans-serif;
}

/* Title */
.gomobd-review-section .gomobd-review-title {
    font-size: 20px;
    color: #222;
}

/* Review Card */
.gomobd-review-section .gomobd-review-card {
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 10px;
    padding: 16px 20px;
    transition: all 0.3s ease-in-out;
}
.gomobd-review-section .gomobd-review-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Header */
.gomobd-review-section .gomobd-review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

/* Avatar */
.gomobd-review-section .gomobd-review-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #198754;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
    margin-right: 12px;
}

/* Name + Date */
.gomobd-review-section .gomobd-review-meta {
    flex-grow: 1;
}
.gomobd-review-section .gomobd-review-name {
    font-size: 16px;
    margin: 0;
    color: #222;
}
.gomobd-review-section .gomobd-review-date {
    font-size: 13px;
    color: #888;
}

/* Stars */
.gomobd-review-section .gomobd-review-stars {
    color: #f8b400;
    font-size: 15px;
}

/* Review Text */
.gomobd-review-section .gomobd-review-body {
    margin-top: 10px;
    color: #555;
    font-size: 15px;
    line-height: 1.6;
}

/* Empty state */
.gomobd-review-section .gomobd-review-empty {
    background: #f9f9f9;
    border-radius: 10px;
    color: #777;
}

/* ✅ Simple Wholesale Pricing Styles */
.wholesale-tier-row:hover {
    background: #f0f8f0 !important;
}

.wholesale-tier-row.active-tier {
    background: #d4edda !important;
    border-left: 3px solid #28a745 !important;
}

/* Review Modal: z-index fix যাতে ব্যাকড্রপের পিছনে না পড়ে এবং ক্লিক/টাইপ কাজ করে */
#exampleModal {
    z-index: 10055 !important;
}
#exampleModal .modal-dialog {
    z-index: 10056 !important;
}
</style>
@endpush

@section('content')
<div class="homeproduct main-details-page">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <section class="product-section">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-6 position-relative">
                                @if($details->old_price)
                                <div class="product-details-discount-badge">
                                    <div class="sale-badge">
                                        <div class="sale-badge-inner">
                                            <div class="sale-badge-box">
                                                <span class="sale-badge-text">
                                                    <p> @php $discount=(((($details->old_price)-($details->new_price))*100) / ($details->old_price)) @endphp {{ number_format($discount, 0) }}%</p>
                                                    ছাড়
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="details_slider owl-carousel" id="details_slider_main">
                                    @foreach ($details->images as $value)
                                        <div class="dimage_item" data-color-id="{{ $value->color_id ?? '' }}">
                                            <img src="{{ asset($value->image) }}" class="block__pic" />
                                        </div>
                                    @endforeach
                                </div>
                                <div
                                    class="indicator_thumb @if ($details->images->count() > 4) thumb_slider owl-carousel @endif" id="indicator_thumb_wrapper">
                                    @foreach ($details->images as $key => $image)
                                        <div class="indicator-item" data-id="{{ $key }}" data-color-id="{{ $image->color_id ?? '' }}">
                                            <img src="{{ asset($image->image) }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="details_right">
                                    <div class="breadcrumb">
                                        <ul>
                                            <li><a href="{{ url('/') }}">Home</a></li>
                                            <li><span>/</span></li>
                                            <li><a
                                                    href="{{ url('/category/' . $details->category->slug) }}">{{ $details->category->name }}</a>
                                            </li>
                                            @if ($details->subcategory)
                                                <li><span>/</span></li>
                                                <li><a
                                                        href="#">{{ $details->subcategory ? $details->subcategory->subcategoryName : '' }}</a>
                                                </li>
                                                @endif @if ($details->childcategory)
                                                    <li><span>/</span></li>
                                                    <li><a
                                                            href="#">{{ $details->childcategory->childcategoryName }}</a>
                                                    </li>
                                                @endif
                                        </ul>
                                    </div>

                                    <div class="product">
                                        <div class="product-cart">
                                            <p class="name">{{ $details->name }}</p>
                                            <p class="details-price">
                                                @if ($details->old_price)
                                                    <del>৳{{ $details->old_price }}</del>
                                                @endif <span id="newPrice">৳{{ $details->new_price }}</span>

                                            </p>
                                            <div class="details-ratting-wrapper">
                                            @php
                                                $averageRating = $reviews->avg('ratting');
                                                $filledStars = floor($averageRating);
                                                $emptyStars = 5 - $filledStars;
                                            @endphp
                                            
                                            @if ($averageRating >= 0 && $averageRating <= 5)
                                                @for ($i = 1; $i <= $filledStars; $i++)
                                                    <i class="fas fa-star"></i>
                                                @endfor
                                            
                                                @if ($averageRating == $filledStars)
                                                    {{-- If averageRating is an integer, don't display half star --}}
                                                @else
                                                    <i class="far fa-star-half-alt"></i>
                                                @endif
                                            
                                                @for ($i = 1; $i <= $emptyStars; $i++)
                                                    <i class="far fa-star"></i>
                                                @endfor
                                            
                                                <span>{{ number_format($averageRating, 2) }}/5</span>
                                            @else
                                                <span>Invalid rating range</span>
                                            @endif
                                            <a class="all-reviews-button" href="#writeReview">See Reviews</a>
                                            </div>
                                            <div class="product-code">
                                                <p><span>প্রোডাক্ট কোড : </span>{{ $details->product_code }}</p>
                                            </div>

                                            {{-- ⭐⭐ এখানে Product Type দেখানো হচ্ছে ⭐⭐ --}}
                                            @php
                                                $productTypeText = $details->is_digital
                                                    ? 'Digital'
                                                    : 'Physical';
                                            @endphp
                                            <div class="pro_brand">
                                                <p>
                                                  Product Type: {{ $productTypeText }}
                                                </p>
                                            </div>
                                            {{-- ⭐⭐ Product Type End ⭐⭐ --}}

                                            {{-- ⭐⭐ Wholesale Pricing Tiers - Simple Clean Design ⭐⭐ --}}
                                            @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
                                            <div class="wholesale-pricing-section" style="margin: 20px 0;">
                                                <h5 style="margin-bottom: 15px; font-size: 16px; font-weight: 600; color: #333;">
                                                    <i class="fa fa-tag me-2"></i> Wholesale Pricing
                                                </h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover mb-0" style="background: #fff;">
                                                        <thead style="background: #f8f9fa;">
                                                            <tr>
                                                                <th style="padding: 12px; font-size: 14px; font-weight: 600;">Quantity</th>
                                                                <th style="padding: 12px; font-size: 14px; font-weight: 600;">Price</th>
                                                                <th style="padding: 12px; font-size: 14px; font-weight: 600;">Stock</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
                                                            <tr class="wholesale-tier-row" 
                                                                data-min-qty="{{ $tier->min_quantity }}" 
                                                                data-max-qty="{{ $tier->max_quantity ?? 999999 }}" 
                                                                data-price="{{ $tier->wholesale_price }}"
                                                                style="cursor: pointer; transition: background 0.2s;">
                                                                <td style="padding: 12px; font-size: 14px;">
                                                                    {{ $tier->min_quantity }}{{ $tier->max_quantity ? ' - ' . $tier->max_quantity : '+' }} pcs
                                                                </td>
                                                                <td style="padding: 12px; font-size: 14px; font-weight: 600; color: #28a745;">
                                                                    ৳{{ number_format($tier->wholesale_price, 2) }}
                                                                </td>
                                                                <td style="padding: 12px; font-size: 14px; color: {{ ($tier->stock ?? 0) > 0 ? '#28a745' : '#dc3545' }};">
                                                                    {{ $tier->stock ?? 0 }} pcs
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <p class="text-muted mt-2 mb-0" style="font-size: 12px;">
                                                    <i class="fa fa-info-circle me-1"></i> Quantity select করলে wholesale price automatically apply হবে
                                                </p>
                                            </div>
                                            @endif
                                            {{-- ⭐⭐ Wholesale Pricing End ⭐⭐ --}}

                                            <form action="{{ route('cart.store') }}" method="POST" name="formName" class="ajax-cart-form" id="productDetailsCartForm" onsubmit="return handleDetailsCartSubmit(event)">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $details->id }}" />


{{-- ✅ Variant-based Color & Size (with your old design style) --}}
@if ($details->variantPrices->count() > 0)
    @php
        $productcolors = $details->variantPrices->pluck('color')->unique('id')->filter();
        $productsizes = $details->variantPrices->pluck('size')->unique('id')->filter();
    @endphp

    {{-- 🎨 Color Section --}}
    @if ($productcolors->count() > 0)
        <div class="pro-color" style="width: 100%;">
            <div class="color_inner">
                <p>Color -</p>
                <div class="size-container">
                    <div class="selector">
                        @foreach ($productcolors as $procolor)
                            <div class="selector-item">
                                {{-- ✅ এখন color_id পাঠানো হচ্ছে (নাম নয়) --}}
                                <input type="radio"
                                    id="fc-option{{ $procolor->id }}"
                                    value="{{ $procolor->id }}"
                                    name="product_color"
                                    class="selector-item_radio emptyalert"
                                    required />
                                <label for="fc-option{{ $procolor->id }}"
                                    style="background-color: {{ $procolor->color ?? '#ccc' }}"
                                    class="selector-item_label">
                                    <span>
                                        <img src="{{ asset('public/frontEnd/images/check-icon.svg') }}" alt="Checked Icon" />
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 📏 Size Section --}}
    @if ($productsizes->count() > 0)
        <div class="pro-size" style="width: 100%;">
            <div class="size_inner">
                <p>Size & Variant - <span class="attibute-name"></span></p>
                <div class="size-container">
                    <div class="selector">
                        @foreach ($productsizes as $prosize)
                            <div class="selector-item">
                                {{-- ✅ এখন size_id পাঠানো হচ্ছে --}}
                                <input type="radio"
                                    id="f-option{{ $prosize->id }}"
                                    value="{{ $prosize->id }}"
                                    name="product_size"
                                    class="selector-item_radio emptyalert"
                                    required />
                                <label for="f-option{{ $prosize->id }}" class="selector-item_label">
                                    {{ $prosize->sizeName ?? $prosize->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif





                                                        @if ($details->pro_unit)
                                                            <div class="pro_unig">
                                                                <label>Unit: {{ $details->pro_unit }}</label>
                                                                <input type="hidden" name="pro_unit"
                                                                    value="{{ $details->pro_unit }}" />
                                                            </div>
                                                        @endif
                                                        <div class="pro_brand">
                                                            <p>Brand :
                                                                {{ $details->brand ? $details->brand->name : 'N/A' }}
                                                            </p>
                                                        </div>

                                                        <div class="row">
                                                            <div class="qty-cart col-sm-12">
                                                                <div class="quantity">
                                                                    <span class="minus">-</span>
                                                                    <input type="text" name="qty"
                                                                        value="1" />
                                                                    <span class="plus">+</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex single_product col-sm-12">
                                                  <input type="submit" class="btn px-4 add_cart_btn" onclick="return sendSuccess();" name="add_cart" value="কার্টে যোগ করুন" />
<input type="submit" class="btn px-4 order_now_btn order_now_btn_m" onclick="return sendSuccess();" name="order_now" value="অর্ডার করুন" />
                                                            </div>
                                                        </div>
                                                        <div class="mt-md-2 mt-2">
                                                            <h4 class="font-weight-bold">
                                                                <a class="btn btn-success w-100 call_now_btn"
                                                                    href="tel: {{ $contact->hotline ?? '01XXX-XXXXXX' }}">
                                                                    <i class="fa fa-phone-square"></i>
                                                                    {{ $contact->hotline ?? '01XXX-XXXXXX' }}
                                                                </a>
                                                            </h4>
                                                        </div>
                                                       <div class="mt-md-2 mt-2">
                                                        <h4 class="font-weight-bold">
                                                            <a class="btn btn-success w-100 call_now_btn"
                                                                href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp ?? '8801519607646' }}&text=হ্যালো, আমি এই পণ্যটির ব্যাপারে জানতে চাই: {{ urlencode(Request::url()) }}"
                                                                target="_blank">
                                                                <i class="fa fa-whatsapp"></i>
                                                                এই পণ্যটি সম্পর্কে জিজ্ঞাসা করুন
                                                            </a>
                                                        </h4>
                                                    </div>

                                                        <div class="mt-md-2 mt-2">
                                                            <div class="del_charge_area">
                                                                <div class="alert alert-info text-xs">
                                                                    <div class="flext_area">
                                                                        <i class="fa-solid fa-cubes"></i>
                                                                        <div>

                                                                            @foreach ($shippingcharge as $key => $value)
                                                                                <span>{{ $value->name }} <br /></span>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                     
                                            </form>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="description-nav-wrapper">
    <div class="container">
        <div class="row">

            <div class="col-sm-12">
                <div class="description-nav">
                    <ul class="desc-nav-ul">
                        {{-- <li class="active">
                            <a href="#specification" target="_self">Specification</a>
                        </li> --}}
                        <li>
                            <a href="#description" target="_self">Description</a>
                        </li>
                        {{-- <li>
                            <a href="#question" target="_self">Questions (0)</a>
                        </li> --}}
                        <li>
                            <a href="#writeReview" target="_self">Reviews ({{ $reviews->count() }}) </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="pro_details_area">
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <div class="description tab-content details-action-box" id="description">
                    <h2>বিস্তারিত</h2>
                    <p>{!! $details->description !!}</p>
                </div>
                <div class="tab-content details-action-box" id="writeReview">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-12">
                                
							  
							  
							  
							<section class="gomobd-review-section mt-5" id="reviewsList">
    <div class="gomobd-review-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h3 class="gomobd-review-title fw-bold mb-2 mb-md-0">
            Customer Reviews ({{ $reviews->count() }})
        </h3>
        <button type="button" class="gomobd-review-btn btn btn-success btn-sm"
            data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="fa fa-edit me-1"></i> Write a Review
        </button>
    </div>

    @if ($reviews->count() > 0)
    <div class="gomobd-review-list row g-3">
        @foreach ($reviews as $review)
        <div class="col-12">
            <div class="gomobd-review-card shadow-sm">
                <div class="gomobd-review-card-header d-flex justify-content-between align-items-start flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="gomobd-review-avatar">
                            {{ strtoupper(substr($review->name, 0, 1)) }}
                        </div>
                        <div class="gomobd-review-meta">
                            <h6 class="gomobd-review-name">{{ $review->name }}</h6>
                            <small class="gomobd-review-date">{{ $review->created_at->format('d M Y') }}</small>
                        </div>
                    </div>
                    <div class="gomobd-review-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $review->ratting)
                                <i class="fa-solid fa-star"></i>
                            @else
                                <i class="fa-regular fa-star"></i>
                            @endif
                        @endfor
                    </div>
                </div>
                <div class="gomobd-review-body mt-2">
                    <p><i class="fa-regular fa-comment-dots text-success me-1"></i> {{ $review->review }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="gomobd-review-empty text-center py-5">
        <i class="fa fa-clipboard-list fs-1 text-muted mb-3"></i>
        <p>This product has no reviews yet.<br><strong>Be the first one to write a review.</strong></p>
    </div>
    @endif
</section>

                            </div>
                        </div>
                    </div>
                </div>
            @php
                $videoType = $details->pro_video_type ?? ($details->pro_video ? 'youtube' : null);
                $hasVideo = ($videoType === 'youtube' && $details->pro_video) || ($videoType === 'upload' && $details->pro_video_path);
            @endphp
            @if($hasVideo)
            <div class="col-sm-4">
                <div class="pro_vide">
                    <h2>ভিডিও</h2>
                    @if($videoType === 'youtube' && $details->pro_video)
                    <iframe width="100%" height="315"
                        src="https://www.youtube.com/embed/{{ $details->pro_video }}" title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                    @elseif($videoType === 'upload' && $details->pro_video_path)
                    <video width="100%" height="315" controls style="border-radius:8px;background:#000;">
                        <source src="{{ asset($details->pro_video_path) }}" type="video/mp4">
                        <source src="{{ asset($details->pro_video_path) }}" type="video/webm">
                        <source src="{{ asset($details->pro_video_path) }}" type="video/ogg">
                        আপনার ব্রাউজার ভিডিও সাপোর্ট করে না।
                    </video>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<section class="related-product-section">
    <div class="container">
        <div class="row">
            <div class="related-title">
                <h5>Related Product</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="product-inner owl-carousel related_slider">
                    @foreach ($products as $key => $value)
                    <div class="product_item wist_item wow zoomIn" data-wow-duration="1.5s"
                        data-wow-delay="0.{{ $key }}s">

                        <div class="product_item_inner">
                            @if($value->old_price)
                            <div class="sale-badge">
                                <div class="sale-badge-inner">
                                    <div class="sale-badge-box">
                                        <span class="sale-badge-text">
                                            <p>@php $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) @endphp 
                                               {{ number_format($discount, 0) }}%</p>
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
                                    <a href="{{ route('product', $value->slug) }}">{{ Str::limit($value->name, 35) }}</a>
                                </div>
                            </div>
                        </div>

                        @php
                            $averageRating = $value->reviews->avg('ratting'); 
                            $filledStars = floor($averageRating);
                            $hasHalfStar = $averageRating - $filledStars >= 0.5;
                            $emptyStars = 5 - $filledStars - ($hasHalfStar ? 1 : 0);
                        @endphp

                        {{-- Stars --}}
                        @for ($i = 0; $i < $filledStars; $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                        @if ($hasHalfStar)
                            <i class="fas fa-star-half-alt"></i>
                        @endif
                        @for ($i = 0; $i < $emptyStars; $i++)
                            <i class="far fa-star"></i>
                        @endfor

                        <div class="pro_price">
                            <p>
                                <del>৳ {{ $value->old_price }}</del>
                                ৳ {{ $value->new_price }}
                            </p>
                        </div>

                        {{-- ⭐⭐⭐ BUTTON AREA (Added) ⭐⭐⭐ --}}
                        @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                        {{-- ভ্যারিয়েন্ট আছে = দুই বাটনই product details page এ যাবে --}}
                        <div class="pro_btn">

                            <a href="{{ route('product', $value->slug) }}" 
                                class="order-btn-link order-btn">
                                অর্ডার করুন
                            </a>

                            <a href="{{ route('product', $value->slug) }}" 
                                class="cart-icon-link cart-icon-btn">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </a>

                        </div>

                        @else
                        {{-- ভ্যারিয়েন্ট নেই = Order Now + Add to Cart --}}
                        <div class="pro_btn">

                            {{-- Order Now --}}
                            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $value->id }}">
                                <input type="hidden" name="qty" value="1">
                                <input type="hidden" name="order_now" value="1">

                                <button type="submit" class="order-btn">
                                    অর্ডার করুন
                                </button>
                            </form>

                            {{-- Add to Cart --}}
                            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $value->id }}">
                                <input type="hidden" name="qty" value="1">

                                <button type="submit" class="cart-icon-btn">
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
    </div>
</section>

{{-- Review Modal: body এর কাছাকাছি রাখা হয়েছে যাতে z-index/stacking issue না হয় --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Your review</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="insert-review">
                    @if (Auth::guard('customer')->user())
                        <form action="{{ route('customer.review') }}" id="review-form" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $details->id }}">
                            <div class="fz-12 mb-2">
                                <div class="rating">
                                    <label title="Excelent">☆ <input required type="radio" name="ratting" value="5" /></label>
                                    <label title="Best">☆ <input required type="radio" name="ratting" value="4" /></label>
                                    <label title="Better">☆ <input required type="radio" name="ratting" value="3" /></label>
                                    <label title="Very Good">☆ <input required type="radio" name="ratting" value="2" /></label>
                                    <label title="Good">☆ <input required type="radio" name="ratting" value="1" /></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="message-text" class="col-form-label">Message:</label>
                                <textarea required class="form-control radius-lg" name="review" id="message-text"></textarea>
                                <span id="validation-message" style="color: red;"></span>
                            </div>
                            <div class="form-group">
                                <button class="details-review-button" type="submit">Submit Review</button>
                            </div>
                        </form>
                    @else
                        <a class="customer-login-redirect" href="{{ route('customer.login') }}">Login to Post Your Review</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection @push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>

<script src="{{ asset('public/frontEnd/js/zoomsl.min.js') }}"></script>
<script>
    // Review modal কে body তে সরিয়ে দেওয়া - #content এর z-index:1 এর জন্য modal backdrop এর পিছনে পড়ছিল
    document.addEventListener('DOMContentLoaded', function() {
        var m = document.getElementById('exampleModal');
        if (m && document.body) document.body.appendChild(m);
    });
</script>
<script>
    const variants = @json($details->variantPrices);

    @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
    var wholesaleTiers = [
        @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
        {
            min_quantity: {{ $tier->min_quantity }},
            max_quantity: {{ $tier->max_quantity ?? 999999 }},
            price: {{ $tier->wholesale_price }}
        }@if(!$loop->last),@endif
        @endforeach
    ];
    var regularPrice = {{ $details->new_price }};

    function getWholesalePrice(qty) {
        for (var i = 0; i < wholesaleTiers.length; i++) {
            if (qty >= wholesaleTiers[i].min_quantity && qty <= wholesaleTiers[i].max_quantity) {
                return wholesaleTiers[i].price;
            }
        }
        return null;
    }
    @endif

    function updateVariantPrice() {
        let color = $("input[name='product_color']:checked").val() || null;
        let size  = $("input[name='product_size']:checked").val() || null;

        let match = null;

        // ✅ color + size (both selected)
        if (color && size) {
            match = variants.find(v => {
                let vColorId = v.color_id ?? v.color;
                let vSizeId = v.size_id ?? v.size;
                return String(vColorId) == String(color) && String(vSizeId) == String(size);
            });
        }

        // ✅ only color (no size selected)
        if (!match && color && !size) {
            match = variants.find(v => {
                let vColorId = v.color_id ?? v.color;
                let vSizeId = v.size_id ?? v.size;
                return String(vColorId) == String(color) && (vSizeId === null || vSizeId === '');
            });
        }

        // ✅ only size (no color selected)
        if (!match && size && !color) {
            match = variants.find(v => {
                let vColorId = v.color_id ?? v.color;
                let vSizeId = v.size_id ?? v.size;
                return String(vSizeId) == String(size) && (vColorId === null || vColorId === '');
            });
        }

        // ✅ update UI
        let basePrice = parseFloat({{ $details->new_price }});
        if (match && match.price !== undefined && match.price !== null) {
            // Variant price is the actual price for this color/size combination
            basePrice = parseFloat(match.price);
        }

        // Apply wholesale price if applicable (wholesale price overrides variant price)
        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
        let qty = parseInt($("input[name='qty']").val()) || 1;
        let wholesalePrice = getWholesalePrice(qty);
        if (wholesalePrice !== null) {
            basePrice = parseFloat(wholesalePrice);
        }
        @endif

        $('#newPrice').text('৳' + basePrice.toFixed(2));
    }
    
    // Call on page load if color/size is already selected
    $(document).ready(function() {
        updateVariantPrice();
    });

    $(document).on(
        'change',
        "input[name='product_color'], input[name='product_size']",
        updateVariantPrice
    );

    // কালার সিলেক্ট করলে ঐ কালারের ইমেজ দেখাবে
    var productImages = @json($details->images->map(function($img) {
        return ['src' => asset($img->image), 'color_id' => $img->color_id];
    }));

    function updateImagesByColor(colorId) {
        var colorIdStr = colorId ? String(colorId) : null;
        var filteredImages = [];

        if (colorIdStr) {
            var colorSpecific = productImages.filter(function(img) {
                return img.color_id && String(img.color_id) === colorIdStr;
            });
            var defaultImages = productImages.filter(function(img) { return !img.color_id; });
            filteredImages = colorSpecific.length > 0 ? colorSpecific : defaultImages;
        } else {
            filteredImages = productImages.filter(function(img) { return !img.color_id; });
            if (filteredImages.length === 0) filteredImages = productImages;
        }
        if (filteredImages.length === 0) filteredImages = productImages;

        var $slider = $(".details_slider");
        var owl = $slider.data("owl.carousel");
        if (owl) owl.destroy();

        var sliderHtml = filteredImages.map(function(img, i) {
            return '<div class="dimage_item"><img src="' + img.src + '" class="block__pic" /></div>';
        }).join('');
        $slider.html(sliderHtml);

        var thumbHtml = filteredImages.map(function(img, i) {
            return '<div class="indicator-item" data-id="' + i + '"><img src="' + img.src + '" /></div>';
        }).join('');
        var $thumbWrapper = $("#indicator_thumb_wrapper");
        var thumbOwl = $thumbWrapper.data("owl.carousel");
        if (thumbOwl) thumbOwl.destroy();
        $thumbWrapper.removeClass("thumb_slider owl-carousel").html(thumbHtml);
        if (filteredImages.length > 4) {
            $thumbWrapper.addClass("thumb_slider owl-carousel");
            $thumbWrapper.owlCarousel({ margin: 15, items: 4, loop: true, dots: false, nav: true, autoplayTimeout: 6000, autoplayHoverPause: true });
        }

        $slider.owlCarousel({
            margin: 15,
            items: 1,
            loop: filteredImages.length > 1,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });

        $(".indicator-item").off("click").on("click", function() {
            var slideIndex = parseInt($(this).data("id"), 10);
            $slider.trigger("to.owl.carousel", slideIndex);
        });

        if ($(".block__pic").length && typeof $(".block__pic").imagezoomsl === "function") {
            $(".block__pic").imagezoomsl({ zoomrange: [3, 3] });
        }
    }

    $(document).on("change", "input[name='product_color']", function() {
        updateImagesByColor($(this).val() || null);
    });
</script>



<script>
    $(document).ready(function() {
        $(".details_slider").owlCarousel({
            margin: 15,
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });
        $(".indicator-item").on("click", function() {
            var slideIndex = $(this).data("id");
            $(".details_slider").trigger("to.owl.carousel", slideIndex);
        });
    });
</script>
<!--Data Layer Start-->
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({
        ecommerce: null
    });
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            items: [{
                item_name: "{{ $details->name }}",
                item_id: "{{ $details->id }}",
                price: "{{ $details->new_price }}",
                item_brand: "{{ $details->brand?$details->brand->name:'' }}",
                item_category: "{{ $details->category->name }}",
                item_variant: "{{ $details->pro_unit }}",
                currency: "BDT",
                quantity: {{ $details->stock ?? 0 }}
            }],
            impression: [
                @foreach ($products as $value)
                    {
                        item_name: "{{ $value->name }}",
                        item_id: "{{ $value->id }}",
                        price: "{{ $value->new_price }}",
                        item_brand: "{{ $details->brand?$details->brand->name:'' }}",
                        item_category: "{{ $value->category ? $value->category->name : '' }}",
                        item_variant: "{{ $value->pro_unit }}",
                        currency: "BDT",
                        quantity: {{ $value->stock ?? 0 }}
                    },
                @endforeach
            ]
        }
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#add_to_cart').click(function() {
            gtag("event", "add_to_cart", {
                currency: "BDT",
                value: "1.5",
                items: [
                    @foreach (Cart::instance('shopping')->content() as $cartInfo)
                        {
                            item_id: "{{$details->id}}",
                            item_name: "{{$details->name}}",
                            price: "{{$details->new_price}}",
                            currency: "BDT",
                            quantity: {{ $cartInfo->qty ?? 0 }}
                        },
                    @endforeach
                ]
            });
        });
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#order_now').click(function() {
            gtag("event", "add_to_cart", {
                currency: "BDT",
                value: "1.5",
                items: [
                    @foreach (Cart::instance('shopping')->content() as $cartInfo)
                        {
                            item_id: "{{$details->id}}",
                            item_name: "{{$details->name}}",
                            price: "{{$details->new_price}}",
                            currency: "BDT",
                            quantity: {{ $cartInfo->qty ?? 0 }}
                        },
                    @endforeach
                ]
            });
        });
    });
</script>

<!-- Data Layer End-->

{{-- 🔹 নতুন dataLayer + Facebook Pixel ইভেন্ট (আগের কিছু না কেটে শুধু যোগ করা) --}}
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];

    (function () {

        var productItem = {
            item_id: "{{ $details->id }}",
            item_name: @json($details->name),
            price: {{ (float) $details->new_price }},
            item_brand: @json(optional($details->brand)->name),
            item_category: @json(optional($details->category)->name),
            item_variant: @json($details->pro_unit),
            currency: "BDT",
            quantity: {{ $details->stock ?? 0 }}
        };

        var relatedItems = [
            @foreach ($products as $value)
            {
                item_id: "{{ $value->id }}",
                item_name: @json($value->name),
                price: {{ (float) $value->new_price }},
                item_brand: @json(optional($value->brand)->name),
                item_category: @json(optional($value->category)->name),
                item_variant: @json($value->pro_unit),
                currency: "BDT",
                quantity: {{ $value->stock ?? 0 }}
            }@if(!$loop->last),@endif
            @endforeach
        ];

        // view_item_list (Related products)
        if (relatedItems.length) {
            window.dataLayer.push({
                event: "view_item_list",
                ecommerce: {
                    item_list_name: "Related Products",
                    currency: "BDT",
                    items: relatedItems
                }
            });
        }

        // Facebook Pixel: ViewContent
        if (typeof fbq === "function") {
            fbq("track", "ViewContent", {
                content_ids: [productItem.item_id],
                content_name: productItem.item_name,
                content_category: productItem.item_category,
                value: productItem.price,
                currency: "BDT"
            });
        }

        // Helper: qty সহ item তৈরি
        function buildCurrentItem() {
            var qtyInput = document.querySelector("input[name='qty']");
            var qty = parseInt(qtyInput ? qtyInput.value : "1", 10);
            if (isNaN(qty) || qty < 1) qty = 1;

            return {
                item_id: productItem.item_id,
                item_name: productItem.item_name,
                price: productItem.price,
                item_brand: productItem.item_brand,
                item_category: productItem.item_category,
                item_variant: productItem.item_variant,
                currency: "BDT",
                quantity: qty
            };
        }

        // "কার্টে যোগ করুন" -> add_to_cart + FB AddToCart
        $(document).on("click", ".add_cart_btn", function () {
            var item  = buildCurrentItem();
            var value = item.price * item.quantity;

            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "add_to_cart",
                ecommerce: {
                    currency: "BDT",
                    value: value,
                    items: [item]
                }
            });

            if (typeof fbq === "function") {
                fbq("track", "AddToCart", {
                    content_ids: [item.item_id],
                    content_name: item.item_name,
                    value: value,
                    currency: "BDT",
                    contents: [
                        { id: item.item_id, quantity: item.quantity }
                    ]
                });
            }
        });

        // "অর্ডার করুন" -> add_to_cart + begin_checkout + FB InitiateCheckout
        $(document).on("click", ".order_now_btn", function () {
            var item  = buildCurrentItem();
            var value = item.price * item.quantity;

            // GA4 add_to_cart
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "add_to_cart",
                ecommerce: {
                    currency: "BDT",
                    value: value,
                    items: [item]
                }
            });

            // GA4 begin_checkout
            window.dataLayer.push({
                event: "begin_checkout",
                ecommerce: {
                    currency: "BDT",
                    value: value,
                    items: [item]
                }
            });

            // FB Pixel
            if (typeof fbq === "function") {
                fbq("track", "AddToCart", {
                    content_ids: [item.item_id],
                    content_name: item.item_name,
                    value: value,
                    currency: "BDT",
                    contents: [
                        { id: item.item_id, quantity: item.quantity }
                    ]
                });

                fbq("track", "InitiateCheckout", {
                    value: value,
                    currency: "BDT",
                    num_items: item.quantity
                });
            }
        });

    })();
</script>

<script>
    $(document).ready(function() {
        $(".related_slider").owlCarousel({
            margin: 10,
            items: 6,
            loop: true,
            dots: true,
            nav: true,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: true,
                    loop: true,
                },
            },
        });
        // $('.owl-nav').remove();
    });
</script>
<script>
    $(document).ready(function() {
        $(".minus").click(function() {
            var $input = $(this).parent().find("input");
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $(".plus").click(function() {
            var $input = $(this).parent().find("input");
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });

        // Wholesale Price Update on Quantity Change - Modern Card Design
        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
        var wholesaleTiers = [
            @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
            {
                min_quantity: {{ $tier->min_quantity }},
                max_quantity: {{ $tier->max_quantity ?? 999999 }},
                price: {{ $tier->wholesale_price }}
            }@if(!$loop->last),@endif
            @endforeach
        ];
        var regularPrice = {{ $details->new_price }};

        function updatePriceBasedOnQuantity() {
            var qty = parseInt($("input[name='qty']").val()) || 1;
            var selectedPrice = regularPrice;
            var matchedTier = null;

            // Find matching wholesale tier
            for (var i = 0; i < wholesaleTiers.length; i++) {
                if (qty >= wholesaleTiers[i].min_quantity && qty <= wholesaleTiers[i].max_quantity) {
                    selectedPrice = wholesaleTiers[i].price;
                    matchedTier = wholesaleTiers[i];
                    break;
                }
            }

            // Update price display
            $('#newPrice').text('৳' + selectedPrice.toFixed(2));

            // Highlight matching tier row
            $('.wholesale-tier-row').removeClass('active-tier');
            if (matchedTier) {
                $('.wholesale-tier-row').each(function() {
                    var minQty = parseInt($(this).data('min-qty'));
                    var maxQty = parseInt($(this).data('max-qty'));
                    if (qty >= minQty && qty <= maxQty) {
                        $(this).addClass('active-tier');
                    }
                });
            }
        }

        // Update price when quantity changes
        $("input[name='qty']").on('change keyup', function() {
            updatePriceBasedOnQuantity();
        });

        // Click on tier row to set quantity to minimum
        $('.wholesale-tier-row').on('click', function() {
            var minQty = parseInt($(this).data('min-qty'));
            $("input[name='qty']").val(minQty).trigger('change');
        });

        // Initial price update
        updatePriceBasedOnQuantity();
        @endif
    });
</script>

<script>
    function sendSuccess() {
        var form = document.forms["formName"];
        if (!form) return true;
        var sizeEl = form["product_size"];
        var colorEl = form["product_color"];
        if (sizeEl) {
            var size = sizeEl.value || "";
            if (size === "") {
                toastr.warning("Please select any size");
                return false;
            }
        }
        if (colorEl) {
            var color = colorEl.value || "";
            if (color === "") {
                toastr.error("Please select any color");
                return false;
            }
        }
        return true;
    }
    function handleDetailsCartSubmit(event) {
        if (!sendSuccess()) return false;
        event.preventDefault();
        // master.blade এ document-level submit লিসেনার আছে; বাবল হলে একই ফর্মে দ্বিতীয়বার POST হয়
        if (event.stopPropagation) event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        var form = document.getElementById("productDetailsCartForm");
        if (!form || form.classList.contains("cart-ajax-submit")) return false;
        var isOrderNow = event.submitter && event.submitter.name === "order_now";
        form.classList.add("cart-ajax-submit");
        var formData = new FormData(form);
        if (isOrderNow) formData.append("order_now", "1");
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "{{ route('cart.store') }}");
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);
        xhr.setRequestHeader("Accept", "application/json");
        xhr.onload = function() {
            form.classList.remove("cart-ajax-submit");
            try {
                var data = JSON.parse(xhr.responseText);
                if (data && data.success) {
                    if (typeof toastr !== "undefined") toastr.success("কার্টে যোগ হয়েছে!", "সফল");
                    if (typeof cart_count === "function") cart_count();
                    if (typeof mobile_cart === "function") mobile_cart();
                    if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                    if (data.redirect || isOrderNow) {
                        window.location.href = data.redirect || "{{ route('customer.checkout') }}";
                    } else {
                        if (typeof runFlyToCart === "function") runFlyToCart($(form), function() {
                            if (typeof openSidebarCart === "function") openSidebarCart();
                        });
                        else if (typeof openSidebarCart === "function") openSidebarCart();
                    }
                } else {
                    form.submit();
                }
            } catch (e) {
                form.submit();
            }
        };
        xhr.onerror = function() {
            form.classList.remove("cart-ajax-submit");
            form.submit();
        };
        var params = new URLSearchParams(formData).toString();
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(params);
        return false;
    }
</script>
<script>
    $(document).ready(function() {
        $(".rating label").click(function() {
            $(".rating label").removeClass("active");
            $(this).addClass("active");
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".thumb_slider").owlCarousel({
            margin: 15,
            items: 4,
            loop: true,
            dots: false,
            nav: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });
    });
</script>

<script type="text/javascript">
    $(".block__pic").imagezoomsl({
        zoomrange: [3, 3]
    });
</script>
@endpush
