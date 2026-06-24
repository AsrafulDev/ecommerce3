@extends('frontEnd.layouts.master') 

@section('title', $seo->meta_title ?? 'Home')

@push('seo')
 
<meta name="app-url" content="{{ url('/') }}" />
<meta name="robots" content="index, follow" />

<meta name="description" content="{{ $seo->meta_description ?? '' }}" />
<meta name="keywords" content="{{ $seo->meta_tags ?? '' }}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{ $seo->meta_title ?? '' }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset($generalsetting->og_baner ?? 'public/assets/images/CurlBazar.png') }}" />
<meta property="og:description" content="{{ $seo->meta_description ?? '' }}" />

@if(!empty($seo->search_console_verification))
<meta name="google-site-verification" content="{{ $seo->search_console_verification }}">
@endif
@endpush 

@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
@endpush 

@section('content')
{{--
    LAYOUT-BASED HOMEPAGE RENDERING
    If an active layout exists (set in Admin → Settings), renders homepage sections
    in the order defined by the admin via the drag-drop Layout Builder.
    Otherwise falls back to the original hardcoded section order.
--}}
@if($activeLayout && $activeLayout->sections->count() > 0)
    {{-- Render sections per active layout --}}
    @foreach($activeLayout->sections as $ls)
        @php
            $sectionSlug = $ls->section->slug ?? '';
        @endphp
        @if($sectionSlug)
            @includeIf('frontEnd.layouts.sections.' . $sectionSlug)
        @endif
    @endforeach
@else
    {{-- Fallback: default section order (no layout selected) --}}
    @include('frontEnd.layouts.sections.main-slider')
    @include('frontEnd.layouts.sections.top-categories')
    @includeIf('frontEnd.layouts.sections.flash-sales')
    @includeIf('frontEnd.layouts.sections.hot-deals')
    @includeIf('frontEnd.layouts.sections.all-products')
    @include('frontEnd.layouts.sections.slider-bottom-ads')
    @includeIf('frontEnd.layouts.sections.category-products')
    @include('frontEnd.layouts.sections.campaign-ads')
    @includeIf('frontEnd.layouts.sections.brands')
    @includeIf('frontEnd.layouts.sections.latest-blogs')
    @includeIf('frontEnd.layouts.sections.customer-reviews')
    @include('frontEnd.layouts.sections.footer-top-ads')
@endif
@endsection 

@push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/jquery.syotimer.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".main_slider").owlCarousel({
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            nav: true,
            autoplayHoverPause: true,
            margin: 0,
            mouseDrag: true,
            smartSpeed: 8000,
            autoplayTimeout: 3000,
            animateOut: "fadeOutRight",
            animateIn: "slideInLeft",

            navText: ["<i class='fa-solid fa-angle-left'></i>",
                "<i class='fa-solid fa-angle-right'></i>"
            ],
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".hotdeals-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: true,
                    loop: false,
                },
            },
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".category-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 5,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 8,
                    nav: true,
                    loop: false,
                },
            },
        });

        $(".product_slider").owlCarousel({
            margin: 15,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 5,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: false,
                },
            },
        });
        
        $(".flash_sale_slider").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 6,
                    nav: false,
                },
                1000: {
                    items: 7,
                    nav: false,
                },
            },
        });
        
        $(".category-sliger").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: false,
                },
                600: {
                    items: 6,
                    nav: false,
                },
                1000: {
                    items: 7,
                    nav: false,
                },
            },
        });
        $(".customer-review").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: false,
                },
            },
        });
    });
</script>

<script>
    $("#simple_timer").syotimer({
        date: new Date("{{$generalsetting->hot_deal_end_date}}T23:59:59"),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",
        periodUnit: "d",
        periodic: false
    });
   $("#flash_sale_timer").syotimer({
        date: new Date("{{$generalsetting->flash_sale_end_date}}T23:59:59"),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",
        periodUnit: "d",
        periodic: false,
    });
</script>
@endpush
