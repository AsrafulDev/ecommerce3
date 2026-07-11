<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
     
        <meta name="csrf-token" content="{{ csrf_token() }}" />
         
		<title>@yield('title') - {{$generalsetting->name}}</title>
		
        <!-- App favicon -->

        <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" alt="{{$generalsetting->name}} Favicon" />
        <meta name="author" content="Creative Design" />
        <link rel="canonical" href="https://creativedesign.com.bd" />
        @stack('seo') 
        @stack('css')
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/bootstrap.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/animate.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/all.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.carousel.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.theme.default.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/mobile-menu.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/select2.min.css')}}" />
        <!-- toastr css -->
        <link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets/css/toastr.min.css" />

        <link rel="stylesheet" href="{{asset('public/frontEnd/css/wsit-menu.css')}}" />
<link rel="stylesheet" href="{{ url('/style.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ url('/dynamic-theme.css') }}?v={{ $activeTheme->updated_at->timestamp ?? time() }}">        
<link rel="stylesheet" href="{{ url('/responsive.css') }}?v={{ time() }}">
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/main.css')}}" />

        <meta name="facebook-domain-verification" content="{{$generalsetting->facebook_verification}}" />
        <meta name="google-site-verification" content="{{$generalsetting->google_verification}}" />

        {{-- Data Layer - must load first for GTM/GA4/Facebook/TikTok --}}
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                pageType: '{{ $pageType ?? (\Illuminate\Support\Facades\Route::currentRouteName() ?? "general") }}',
                pageTitle: '{{ addslashes($generalsetting->name ?? "") }}',
                siteName: '{{ addslashes($generalsetting->name ?? "") }}',
                currency: 'BDT',
                language: 'bn'
            });
            @stack('dataLayer')
        </script>

        {{-- 🎯 Google Analytics 4 (gtag.js) — auto-initialized when Measurement ID is set --}}
        @php
            $ga4Setting = \App\Models\GoogleAnalyticSetting::first();
        @endphp
        @if($ga4Setting && $ga4Setting->status && $ga4Setting->measurement_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Setting->measurement_id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $ga4Setting->measurement_id }}');
        </script>
        @endif

		{{-- 🎨 Theme CSS Variables — injected from active theme --}}
		<style>
            :root {
                --primary-color: {{ $activeTheme->primary_color ?? '#0d6efd' }};
                --secondary-color: {{ $activeTheme->secondary_color ?? '#198754' }};
                --accent-color: {{ $activeTheme->accent_color ?? '#ff6a00' }};
                --text-color: {{ $activeTheme->text_color ?? '#212529' }};
                --heading-color: {{ $activeTheme->heading_color ?? '#111111' }};
                --body-bg: {{ $activeTheme->body_bg_color ?? '#ffffff' }};
                --header-bg: {{ $activeTheme->header_bg_color ?? '#ffffff' }};
                --header-text: {{ $activeTheme->header_text_color ?? '#212529' }};
                --footer-bg: {{ $activeTheme->footer_bg_color ?? '#1a1a1a' }};
                --footer-text: {{ $activeTheme->footer_text_color ?? $activeTheme->copyright_text_color ?? '#ffffff' }};
                --copyright-bg: {{ $activeTheme->copyright_bg_color ?? '#000000' }};
                --copyright-text: {{ $activeTheme->copyright_text_color ?? '#ffffff' }};
                --button-bg: {{ $activeTheme->button_bg_color ?? '#0d6efd' }};
                --button-text: {{ $activeTheme->button_text_color ?? '#ffffff' }};
                --button-hover-bg: {{ $activeTheme->button_hover_bg_color ?? '#0b5ed7' }};
                --border-color: {{ $activeTheme->border_color ?? '#dee2e6' }};
                --sale-badge-bg: {{ $activeTheme->sale_badge_bg ?? '#dc3545' }};
                --sale-badge-text: {{ $activeTheme->sale_badge_text ?? '#ffffff' }};
                --font-family: {{ $activeTheme->font_family ?? "'Roboto', sans-serif" }};
                --heading-font: {{ $activeTheme->heading_font ?? "'Jost', sans-serif" }};
                --body-font-size: {{ $activeTheme->body_font_size ?? '14px' }};
                --border-radius: {{ $activeTheme->border_radius ?? '8px' }};
                --card-shadow: {{ $activeTheme->card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)' }};
                --layout-style: {{ $activeTheme->layout_style ?? 'contained' }};
            }
		</style> 
		

        @php
            $pixels = $pixels ?? collect();
            $gtm_code = $gtm_code ?? collect();
            $tiktok_pixels = $tiktok_pixels ?? collect();
        @endphp
        @foreach($pixels as $pixel)
        <!-- Facebook Pixel Code -->
        <script>
            !(function (f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function () {
                    n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = "2.0";
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s);
            })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
            fbq("init", "{{{$pixel->code}}}");
            fbq("track", "PageView");
        </script>
        <noscript>
            <img height="1" width="1" style="display: none;" src="https://www.facebook.com/tr?id={{{$pixel->code}}}&ev=PageView&noscript=1" />
        </noscript>
        <!-- End Facebook Pixel Code -->
        @endforeach
        
        @foreach($gtm_code as $gtm)
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ str_starts_with($gtm->code, "GTM-") ? $gtm->code : "GTM-".$gtm->code }}');</script>
        <!-- End Google Tag Manager -->
        @endforeach

        @foreach($tiktok_pixels as $tp)
        <!-- TikTok Pixel -->
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                ttq.load('{{ $tp->code }}');
                ttq.page();
            }(window, document, 'ttq');
        </script>
        <!-- End TikTok Pixel -->
        @endforeach
        <style>
            .whatsapp-float {
                position: fixed;
                bottom: 20px; /* Adjust vertical position */
                left: 20px; /* Adjust horizontal position */
                z-index: 1000;
                background-color: #25D366;
                color: white;
                border-radius: 50%;
                padding: 15px;
                font-size: 24px; /* Icon size */
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
            }
        
            .whatsapp-float:hover {
                color: white;
                box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            }
            /* Hide the .whatsapp-float element on mobile devices */
            @media (max-width: 768px) {
                .whatsapp-float {
                    display: none;
                }
            }
            .stock-out-overlay {
                position: absolute;
                top: 50%;
                left: 0;
                transform: translateY(-50%);
                width: 100%;
                background-color: white;
                color: black;
                font-size: 1em;
                opacity:0.8;
                font-weight: bold;
                text-align: center;
                padding: 10px 0;
                overflow: hidden;
                white-space: nowrap;
            }
            /* Facebook icon */
            .social_list .fa-facebook-f {
                padding:5px 8px;
                color:white;
                background-color: #3b5998; 
                
            }
            
            .social_list .fa-facebook-f:hover {
                background-color: #2d4373;  /* Darker Facebook blue on hover */
            }
            
            /* Twitter icon */
            .social_list .fa-twitter {
                padding:5px 8px;
                color:white;
                background-color: #1da1f2;  /* Twitter blue */
            }
            
            .social_list .fa-twitter:hover {
                padding:5px 8px;
                color:white;
                background-color: #0c85d0;  /* Darker Twitter blue on hover */
            }
            
            /* Instagram icon */
            .social_list .fa-instagram {
                padding:5px 8px;
                color:white;
                background-color: #e4405f;  /* Instagram pink */
            }
            
            .social_list .fa-instagram:hover {
                padding:5px 8px;
                color:white;
                background-color: #bc2a8d;  /* Darker Instagram purple-pink on hover */
            }
            
            /* LinkedIn icon */
            .social_list .fa-linkedin {
                padding:5px 8px;
                color:white;
                background-color: #0077b5;  /* LinkedIn blue */
            }
            
            .social_list .fa-linkedin:hover {
                background-color: #005983;  /* Darker LinkedIn blue on hover */
            }
            
            /* WhatsApp icon */
            .social_list .fa-whatsapp {
                padding:5px 8px;
                color:white;
                background-color: #25d366;  /* WhatsApp green */
            }
            
            .social_list .fa-whatsapp:hover {
                background-color: #128c7e;  /* Darker WhatsApp green on hover */
            }
            
            /* YouTube icon */
            .social_list .fa-youtube {
                padding:5px 8px;
                color:white;
                background-color: #ff0000;  /* YouTube red */
            }
            
            .social_list .fa-youtube:hover {
                background-color: #cc0000;  /* Darker YouTube red on hover */
            }

            /* Footer Background Fix - Ensure footer always displays correctly on all pages */
            /* Note: Footer colors are set via inline styles and style.blade.php */
            footer {
                position: relative !important;
                z-index: 999 !important;
                clear: both !important;
                margin-top: 0 !important;
            }
            
            /* Ensure content doesn't overlap footer */
            #content {
                position: relative;
                z-index: 1;
                background: transparent !important;
                background-color: transparent !important;
            }
            
            /* Prevent body background from affecting footer */
            body {
                background: transparent !important;
                background-color: transparent !important;
            }

            /* App Store Badges Styles */
            .app-badges {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-top: 15px;
            }
            
            .app-badge-btn {
                display: inline-block;
                transition: transform 0.3s ease, opacity 0.3s ease;
                text-decoration: none;
            }
            
            .app-badge-btn:hover {
                transform: scale(1.05);
                opacity: 0.9;
            }
            
            .app-badge-btn img {
                height: 35px !important;
                max-height: 35px !important;
                width: auto !important;
                max-width: 140px !important;
                display: block;
            }
            
            /* Responsive App Badges */
            @media (max-width: 768px) {
                .app-badges {
                    gap: 10px;
                }
                
                .app-badge-btn img {
                    height: 32px !important;
                    max-height: 32px !important;
                    max-width: 130px !important;
                }
            }
            
            @media (max-width: 576px) {
                .app-badge-btn img {
                    height: 30px !important;
                    max-height: 30px !important;
                    max-width: 120px !important;
                }
            }

            /* Newsletter Subscribe Styles */
            .footer-newsletter {
                margin: 15px 0 25px 0;
                padding: 15px;
                border-radius: 8px;
                border: none;
            }
            .footer-newsletter .form-control.newsletter-input {
                border:none;
                border-radius:50px 0 0 50px;
            }
            
            /* Newsletter Title Color - White */
            .footer-menu .title.stay_conn a {
                color: var(--footer-text) !important;
            }
            
            .footer-menu ul + ul {
                margin-top: 20px;
            }

            .modern-subscribe {
                width: 100%;
            }

            .newsletter-input-group {
                display: flex;
                flex-direction: row;
                gap: 0;
                width: 100%;
                border-radius: 50px;
                overflow: hidden;
                padding: 2px;
            }

            .newsletter-input {
                flex: 1;
                padding: 12px 20px;
                border: none;
                border-radius: 50px 0 0 50px;
                color: #fff;
                font-size: 14px;
                transition: all 0.3s ease;
            }

            .newsletter-input::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }

            .newsletter-input:focus {
                outline: none;
            }

            .newsletter-btn {
                padding: 12px 20px;
                border-radius: 50px;
                border: none;
                color: #fff;
                font-weight: 500;
                font-size: 16px;
                transition: all 0.3s ease;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 50px;
            }

            .newsletter-btn:hover {
                transform: scale(1.05);
                color: #fff;
                opacity: 0.85;
            }

            .newsletter-btn i {
                font-size: 16px;
            }

            /* Responsive Styles for Newsletter */
            @media (max-width: 768px) {
                .footer-newsletter {
                    margin: 12px 0 15px 0;
                }

                .newsletter-input-group {
                    flex-direction: row;
                    border-radius: 50px;
                }

                .newsletter-input {
                    border-radius: 50px 0 0 50px;
                    padding: 10px 18px;
                    font-size: 13px;
                }

                .newsletter-btn {
                    border-radius: 50px;
                    padding: 10px 18px;
                    min-width: 50px;
                }
            }

            @media (max-width: 576px) {
                .newsletter-input {
                    padding: 10px 15px;
                    font-size: 13px;
                }

                .newsletter-btn {
                    padding: 10px 15px;
                    font-size: 14px;
                }
            }

            /* Top headline scrolling text - CSS animation */
            .headline-scroll-wrapper {
                display: flex;
                width: max-content;
                animation: headlineScroll 25s linear infinite;
                white-space: nowrap;
            }
            .headline-scroll-text {
                padding-right: 3em;
                flex-shrink: 0;
            }
            .headline-scroll-duplicate { padding-right: 3em; }
            @keyframes headlineScroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
        </style>
        {!! $generalsetting->header_code !!}
    </head>
    <body class="gotop">
       
        @php $subtotal = Cart::instance('shopping')->subtotal(); @endphp
        <div class="mobile-menu">
                <div class="mobile-menu-logo">
                    <div class="logo-image">
                        <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.svg') }}" alt="" />
                    </div>
                    <div class="mobile-menu-close">
                        <i class="fa fa-times"></i>
                    </div>
                </div>
                <ul class="first-nav">
                    @foreach($menucategories as $scategory)
                    <li class="parent-category">
                        <a href="{{url('category/'.$scategory->slug)}}" class="menu-category-name">
                            <img src="{{asset($scategory->image)}}" alt="" class="side_cat_img" />
                            {{$scategory->name}}
                        </a>
                        @if($scategory->subcategories->count() > 0)
                        <span class="menu-category-toggle">
                            <i class="fa fa-chevron-down"></i>
                        </span>
                        @endif
                        <ul class="second-nav" style="display: none;">
                            @foreach($scategory->subcategories as $subcategory)
                            <li class="parent-subcategory">
                                <a href="{{url('subcategory/'.$subcategory->slug)}}" class="menu-subcategory-name">{{$subcategory->subcategoryName}}</a>
                                @if($subcategory->childcategories->count() > 0)
                                <span class="menu-subcategory-toggle"><i class="fa fa-chevron-down"></i></span>
                                @endif
                                <ul class="third-nav" style="display: none;">
                                    @foreach($subcategory->childcategories as $childcat)
                                    <li class="childcategory"><a href="{{url('products/'.$childcat->slug)}}" class="menu-childcategory-name">{{$childcat->childcategoryName}}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach
                </ul>
            </div>
        {{-- Dynamic Header — style from admin Header/Footer Builder --}}
        @php
            $headerStyle = $generalsetting->header_style ?? 'custom';
            $headerView = 'frontEnd.layouts.headers.' . $headerStyle;
        @endphp
        @include($headerView)
        <div id="content">
            @yield('content')
        </div>
            <!-- content end -->
        {{-- Dynamic Footer — style from admin Header/Footer Builder --}}
        @php
            $footerStyle = $generalsetting->footer_style ?? 'custom';
            $footerView = 'frontEnd.layouts.footers.' . $footerStyle;
        @endphp
        @includeIf($footerView)

        {{-- Floating Cart - ক্লিক করলে ডান দিকের সাইডবার কার্ট ওপেন হবে --}}
        <a href="javascript:void(0)" class="floating-cart-widget" id="floatingCartBtn" title="{{ __('View Cart') }}">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="floating-cart-badge mobilecart-qty">{{ Cart::instance('shopping')->count() }}</span>
        </a>

        {{-- Sidebar Cart Drawer - ডান দিক থেকে স্লাইড আউট --}}
        <div id="sidebarCartOverlay" class="sidebar-cart-overlay" onclick="closeSidebarCart()"></div>
        <div id="sidebarCartDrawer" class="sidebar-cart-drawer">
            <div id="sidebarCartContent">
                {{-- AJAX দিয়ে লোড হবে --}}
            </div>
        </div>

        <style>
            .floating-cart-widget {
                position: fixed;
                top: 50%;
                right: 0;
                transform: translateY(-50%);
                width: 52px;
                height: 70px;
                background: var(--primary-color);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px 0 0 12px;
                box-shadow: -3px 0 15px color-mix(in srgb, var(--primary-color) 40%, transparent);
                z-index: 9998;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            .floating-cart-widget:hover {
                color: #fff;
                width: 56px;
                box-shadow: -4px 0 20px color-mix(in srgb, var(--primary-color) 50%, transparent);
            }
            .floating-cart-widget i {
                font-size: 24px;
            }
            .floating-cart-badge {
                position: absolute;
                top: -6px;
                left: 50%;
                transform: translateX(-50%);
                min-width: 22px;
                height: 22px;
                background: #fff;
                color: var(--primary-color);
                font-size: 11px;
                font-weight: bold;
                border-radius: 50%;
                border: 2px solid var(--primary-color);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
            }
            @media (max-width: 768px) {
                .floating-cart-widget {
                    top: 35%;
                    width: 48px;
                    height: 60px;
                    z-index: 9999;
                }
                .floating-cart-widget i { font-size: 20px; }
                .floating-cart-badge { min-width: 20px; height: 20px; font-size: 10px; }
            }
            .sidebar-cart-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.4);
                z-index: 10010;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s, visibility 0.3s;
            }
            .sidebar-cart-overlay.active {
                opacity: 1;
                visibility: visible;
            }
            .sidebar-cart-drawer {
                position: fixed;
                top: 0;
                right: 0;
                width: 380px;
                max-width: 95vw;
                height: 100%;
                height: 100dvh;
                background: #fff;
                z-index: 10011;
                transform: translateX(100%);
                transition: transform 0.35s ease;
                box-shadow: -5px 0 25px rgba(0,0,0,0.15);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .sidebar-cart-drawer.active {
                transform: translateX(0);
            }
            #sidebarCartContent {
                height: 100%;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .sidebar-cart-header {
                color: #fff;
                padding: 16px 20px;
                display: flex;
                align-items: center;
                gap: 12px;
                flex-shrink: 0;
            }
            .sidebar-cart-close {
                background: transparent;
                border: none;
                color: #fff;
                font-size: 22px;
                cursor: pointer;
                padding: 4px;
                line-height: 1;
            }
            .sidebar-cart-close:hover { opacity: 0.9; }
            .sidebar-cart-title {
                font-size: 20px;
                font-weight: 700;
                margin: 0;
                flex: 1;
            }
            .sidebar-cart-body {
                flex: 0 0 auto;
                padding: 16px;
                background: #f8f9fa;
            }
            .sidebar-cart-item {
                display: flex;
                gap: 12px;
                padding: 12px;
                background: #fff;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            .sidebar-cart-item-img {
                width: 70px;
                height: 85px;
                flex-shrink: 0;
                border-radius: 6px;
                overflow: hidden;
            }
            .sidebar-cart-item-img img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .sidebar-cart-item-details {
                flex: 1;
                min-width: 0;
                position: relative;
            }
            .sidebar-cart-item-title {
                font-weight: 600;
                color: #222;
                text-decoration: none;
                display: block;
                margin-bottom: 4px;
                font-size: 14px;
                line-height: 1.3;
            }
            .sidebar-cart-item-title:hover { color: var(--primary-color); }
            .sidebar-cart-item-price {
                font-size: 13px;
                color: #444;
                margin: 0 0 4px 0;
            }
            .sidebar-cart-item-savings {
                font-size: 12px;
                color: #28a745;
                font-weight: 500;
                margin: 0 0 8px 0;
            }
            .sidebar-cart-item-remove {
                position: absolute;
                bottom: 0;
                right: 0;
                background: none;
                border: none;
                color: var(--primary-color);
                cursor: pointer;
                padding: 4px;
                font-size: 14px;
            }
            .sidebar-cart-item-remove:hover { opacity: 0.85; }
            .sidebar-cart-qty {
                display: flex;
                align-items: center;
                width: fit-content;
                border: 1px solid #ddd;
                border-radius: 6px;
                overflow: hidden;
                margin: 8px 0 6px 0;
            }
            .sidebar-qty-btn {
                width: 28px;
                height: 28px;
                border: none;
                background: #f0f0f0;
                color: #333;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }
            .sidebar-qty-btn:hover {
                background: var(--primary-color);
                color: #fff;
            }
            .sidebar-qty-num {
                min-width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 14px;
                background: #fff;
            }
            .sidebar-cart-empty {
                text-align: center;
                padding: 40px 20px;
                color: #888;
            }
            .sidebar-cart-empty i {
                font-size: 48px;
                margin-bottom: 12px;
                opacity: 0.5;
            }
            .sidebar-cart-footer {
                padding: 16px 20px;
                border-top: 1px solid #eee;
                background: #fff;
            }
            .sidebar-cart-total {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                margin-bottom: 12px;
            }
            .sidebar-cart-total-label {
                font-size: 14px;
                color: #666;
            }
            .sidebar-cart-total-amount {
                font-size: 20px;
                font-weight: 700;
                color: #222;
            }
            .sidebar-cart-checkout-btn {
                display: block;
                width: 100%;
                padding: 14px 24px;
                color: #fff !important;
                text-align: center;
                font-weight: 600;
                font-size: 16px;
                border-radius: 6px;
                text-decoration: none;
                transition: opacity 0.2s;
            }
            .sidebar-cart-checkout-btn:hover {
                opacity: 0.9;
                color: #fff !important;
            }
            @media (max-width: 768px) {
                .sidebar-cart-drawer {
                    width: 100%;
                    max-width: 100%;
                }
                .sidebar-cart-header { padding: 14px 16px; }
                .sidebar-cart-body { padding: 12px; }
                .sidebar-cart-item { padding: 10px; }
                .sidebar-cart-item-img { width: 60px; height: 72px; }
                .sidebar-qty-btn { width: 36px; height: 36px; }
                .sidebar-qty-num { min-width: 36px; height: 36px; }
            }
            /* Fly to cart animation */
            .fly-to-cart-img {
                position: fixed;
                z-index: 99999;
                pointer-events: none;
                border-radius: 8px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.35);
                object-fit: cover;
                border: 2px solid #fff;
            }
            @keyframes cartBump {
                0% { transform: scale(1); }
                40% { transform: scale(1.25); }
                70% { transform: scale(0.95); }
                100% { transform: scale(1); }
            }
            .cart-bump-animate {
                animation: cartBump 0.45s ease-out;
            }
            .floating-cart-widget, .menu-bag a, .mobile_bottom_nav .nav_item .icon_box {
                transform-origin: center center;
            }
        </style>

        <script>
        function openSidebarCart() {
            document.getElementById("sidebarCartOverlay").classList.add("active");
            document.getElementById("sidebarCartDrawer").classList.add("active");
            document.body.style.overflow = "hidden";
            if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
        }
        function closeSidebarCart() {
            document.getElementById("sidebarCartOverlay").classList.remove("active");
            document.getElementById("sidebarCartDrawer").classList.remove("active");
            document.body.style.overflow = "";
        }
        function sidebarCartRefresh() {
            $.get("{{ route('cart.sidebar') }}", function(html) {
                $("#sidebarCartContent").html(html);
                if (typeof feather !== "undefined") feather.replace();
            });
        }
        document.getElementById("floatingCartBtn") && document.getElementById("floatingCartBtn").addEventListener("click", function(e) {
            e.preventDefault();
            openSidebarCart();
        });
        document.getElementById("sidebarCartOverlay") && document.getElementById("sidebarCartOverlay").addEventListener("click", closeSidebarCart);
        </script>

       <div class="mobile_bottom_nav">
    <div class="nav_container">
        <a href="javascript:void(0)" class="nav_item toggle">
            <div class="icon_box">
                <i class="fa-solid fa-bars"></i>
            </div>
            <span class="nav_text">{{ __('Category') }}</span>
        </a>

        <a href="{{route('customer.order_track')}}" class="nav_item {{ Route::is('customer.order_track') ? 'active' : '' }}">
            <div class="icon_box">
                <i class="fa fa-truck"></i>
            </div>
            <span class="nav_text">Tracking</span>
        </a>

        <div class="nav_item home_wrapper">
            <a href="{{route('home')}}" class="home_fab {{ Route::is('home') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
            </a>
        </div>

        <a href="{{route('customer.checkout')}}" class="nav_item {{ Route::is('customer.checkout') ? 'active' : '' }}">
            <div class="icon_box">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart_badge mobilecart-qty">{{Cart::instance('shopping')->count()}}</span>
            </div>
            <span class="nav_text">Cart</span>
        </a>

        @if(Auth::guard('customer')->user())
            <a href="{{route('customer.account')}}" class="nav_item {{ Route::is('customer.account') ? 'active' : '' }}">
                <div class="icon_box">
                    <i class="fa-solid fa-user"></i>
                </div>
                <span class="nav_text">{{ __('Account') }}</span>
            </a>
        @else
            <a href="{{route('customer.login')}}" class="nav_item {{ Route::is('customer.login') ? 'active' : '' }}">
                <div class="icon_box">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <span class="nav_text">{{ __('Login') }}</span>
            </a>
        @endif
    </div>
</div>
<style>
/* --- Mobile Bottom Navigation Styles --- */
.mobile_bottom_nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    padding: 10px 0;
    border-radius: 20px 20px 0 0; /* উপরের কোনা গুলো একটু গোল হবে */
    display: none; /* ডেস্কটপে হাইড থাকবে */
}

/* শুধুমাত্র মোবাইলে দেখানোর জন্য */
@media (max-width: 768px) {
    .mobile_bottom_nav {
        display: block;
    }
}

.nav_container {
    display: flex;
    justify-content: space-around;
    align-items: flex-end; /* আইটেমগুলো নিচে সমান থাকবে */
    position: relative;
    padding: 0 10px;
}

/* সাধারণ মেনু আইটেম */
.nav_item {
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #6c757d; /* ডিফল্ট কালার */
    font-size: 12px;
    transition: all 0.3s ease;
    width: 20%;
}

.icon_box {
    position: relative;
    font-size: 20px;
    margin-bottom: 4px;
    transition: transform 0.2s;
}

.nav_text {
    font-weight: 500;
}

/* হোভার এবং একটিভ কালার */
.nav_item:hover, .nav_item.active {
    color: #FF6600; /* আপনার ব্র্যান্ড কালার এখানে দিন */
}

.nav_item.active .icon_box {
    transform: translateY(-3px); /* একটিভ হলে একটু উপরে উঠবে */
}

/* --- Center Floating Home Button --- */
.home_wrapper {
    position: relative;
    bottom: 25px; /* স্বাভাবিকের চেয়ে উপরে থাকবে */
}

.home_fab {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
    font-size: 24px;
    box-shadow: 0 8px 15px rgba(255, 102, 0, 0.4);
    border: 4px solid #fff; /* সাদা বর্ডার */
    transition: transform 0.3s ease;
}

.home_fab:hover {
    transform: scale(1.1); /* হোভারে বড় হবে */
    color: #fff;
}

/* --- Cart Badge Style --- */
.cart_badge {
    position: absolute;
    top: -8px;
    right: -10px;
    background: #ff0000;
    color: #fff;
    font-size: 10px;
    font-weight: bold;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 2px solid #fff;
}
</style>
        
<!-- ЁЯМР Floating Chat Widget -->
<div class="chat-widget">
  <!-- Main Toggle Button -->
  <div class="chat-toggle" id="chatToggle">
    <i class="fas fa-comment-dots"></i>
  </div>

  <!-- Chat Options -->
  <div class="chat-options" id="chatOptions">
          <a href="https://m.me/{{$generalsetting->facebook_page_username}}" target="_blank" class="chat-btn messenger" title="Messenger">
      <i class="fab fa-facebook-messenger"></i>
    </a>
          <a href="https://wa.me/{{ $contact->whatsapp ?? '8801519607646' }}" target="_blank" class="chat-btn whatsapp" title="WhatsApp">
      <i class="fab fa-whatsapp"></i>
    </a>
    <a href="tel:{{ $contact->hotline ?? '01XXX-XXXXXX' }}" class="chat-btn hotline" title="Hotline">
      <i class="fas fa-phone"></i>
    </a>


  </div>
</div>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Floating Container */
.chat-widget {
  position: fixed;
  bottom: 60px; /* ⬅ Chat icon এখন 55px উপরে */
  right: 25px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

/* Main Toggle Button */
.chat-toggle {
  background: linear-gradient(135deg, #25D366, #128C7E);
  color: #fff;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.3s ease;
  font-size: 26px;
}
.chat-toggle:hover {
  transform: scale(1.1);
}

/* Chat Options Hidden by Default */
.chat-options {
  display: none;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 10px;
  align-items: flex-end;
}

/* Each Chat Button */
.chat-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}
.chat-btn:hover {
  transform: translateY(-3px);
}

/* Button Colors */
.chat-btn.whatsapp { background: #25D366; }
.chat-btn.messenger { background: #0084FF; }
.chat-btn.instagram { background: #E1306C; }
.chat-btn.hotline { background: #FF3B30; }

/* Animation */
.chat-options.show {
  display: flex;
  animation: fadeInUp 0.3s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Tooltip */
.chat-btn[title]:hover::after {
  content: attr(title);
  position: absolute;
  right: 65px;
  background: #222;
  color: #fff;
  padding: 5px 10px;
  font-size: 13px;
  border-radius: 6px;
  white-space: nowrap;
  opacity: 0.9;
}

</style>
@php
    $popup = App\Models\Popup::where('status', 1)->latest()->first();
    // শুধু ইমেইজ দেখাবে যখন বর্ণনা ও লিংক খালি — btn_text ইগনোর (ডিফল্ট "Shop Now" থাকলেও)
    $popupImageOnly = $popup && !trim($popup->description ?? '') && !trim($popup->link ?? '');
@endphp
@if($popup)
<div class="modal fade pop-banner-modal" id="popShopModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" style="z-index: 10000;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $popupImageOnly ? 'pop-banner-dialog' : 'modal-lg' }}">
        <div class="modal-content ps-content {{ $popupImageOnly ? 'ps-content-image-only' : '' }}">
            <button type="button" class="ps-close pop-banner-close" data-bs-dismiss="modal" aria-label="বন্ধ করুন">
                <span aria-hidden="true">&times;</span>
            </button>
            
            @if($popupImageOnly)
            {{-- শুধু ইমেইজ পপআপ - বানার স্টাইল --}}
            <div class="ps-image-only-wrap">
                @if(trim($popup->link ?? ''))
                <a href="{{ $popup->link }}" target="_blank" class="d-block">
                    <img src="{{ url('public/'.$popup->image) }}" alt="{{ $popup->title ?? 'Fabrilife and bkash promotion' }}" class="img-fluid w-100">
                </a>
                @else
                <img src="{{ url('public/'.$popup->image) }}" alt="{{ $popup->title ?? 'Promotional offer' }}" class="img-fluid w-100">
                @endif
            </div>
            @else
            {{-- টেক্সট + ইমেইজ পপআপ --}}
            <div class="ps-layout">
                <div class="ps-text-section">
                    <h3 class="ps-brand">{{ $popup->title ?? 'Offer' }}</h3>
                    
                    <div class="ps-headline">
                        <p>{!! nl2br(e($popup->description ?? '')) !!}</p>
                    </div>

                    @if($popup->offer_end_text)
                    <p class="ps-deadline">{{ $popup->offer_end_text }}</p>
                    @endif

                    <a href="{{ $popup->link ?? '#' }}" class="ps-btn">
                        {{ $popup->btn_text ?? 'Shop the Sale' }}
                    </a>

                    <div class="ps-footer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                          <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
                        </svg>
                        <span>POWERED BY <strong>{{ $generalsetting->name ?? 'CommerceGurus' }}</strong></span>
                    </div>
                </div>

                <div class="ps-image-section">
                    <img src="{{ url('public/'.$popup->image) }}" alt="{{ $popup->title ?? 'Offer' }}">
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Modal Container */
    .ps-content {
        border: none;
        border-radius: 0px; /* Sharp corners like the image */
        overflow: hidden;
        background-color: #fff;
        box-shadow: 0 15px 50px rgba(0,0,0,0.3);
        max-width: 850px; /* Width matching the reference */
        margin: 0 auto;
    }

    /* Flex Layout */
    .ps-layout {
        display: flex;
        flex-direction: row;
        min-height: 450px;
    }

    /* === Left Side Styling === */
    .ps-text-section {
        width: 50%;
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: left;
        position: relative;
    }

    /* Brand: Serif, Reddish-Brown */
    .ps-brand {
        color: #b93a3a; /* Exact color from image */
        font-family: 'Georgia', 'Times New Roman', serif;
        font-weight: 700;
        font-size: 38px;
        margin-bottom: 15px;
        line-height: 1;
    }

    /* Headline: Bold, Sans-Serif */
    .ps-headline {
        color: #222;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 15px;
    }
    
    /* Description text style if multiple lines */
    .ps-headline span, .ps-headline p {
        font-weight: 400;
        font-size: 16px;
        color: #555;
        margin-top: 10px;
    }

    /* Deadline Text: Small, Gray */
    .ps-deadline {
        color: #888;
        font-size: 14px;
        margin-bottom: 30px;
    }

    /* Button: Dark, Rectangular */
    .ps-btn {
        background-color: #2c3e50; /* Dark Charcoal */
        color: #fff !important;
        text-decoration: none;
        padding: 15px 30px;
        text-align: center;
        font-weight: 600;
        font-size: 16px;
        border-radius: 2px; /* Slight radius but mostly sharp */
        display: block;
        width: 100%;
        transition: 0.3s;
    }
    .ps-btn:hover {
        background-color: #000;
    }

    /* Footer / Powered By */
    .ps-footer {
        margin-top: 40px;
        font-size: 10px;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
    }
    .ps-footer strong { color: #333; }

    /* === Right Side Styling === */
    .ps-image-section {
        width: 50%;
        position: relative;
        background: #f0f0f0;
    }
    .ps-image-section img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ensures image covers full height */
        display: block;
    }

    /* Close Button */
    .ps-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #fff;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        font-size: 24px;
        line-height: 32px;
        color: #333;
        cursor: pointer;
        z-index: 1050;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        transition: 0.2s;
    }
    .ps-close:hover {
        color: #b93a3a;
        transform: scale(1.1);
    }

    /* === Fabrilife-style promo popup (ইমেইজ-অনলি) === */
    body:has(#popShopModal.show) .modal-backdrop {
        opacity: 0.72 !important;
        background-color: #0d0d0d !important;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    .pop-banner-modal .modal-dialog.pop-banner-dialog {
        max-width: min(560px, 95vw);
        width: 100%;
        margin: 1rem auto;
    }

    .ps-content-image-only {
        max-width: 100% !important;
        border-radius: 16px !important;
        overflow: visible !important;
        background: #fff !important;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
        position: relative;
        padding: 10px;
    }

    .ps-content-image-only .ps-image-only-wrap {
        padding: 0;
        line-height: 0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }

    .ps-content-image-only .ps-image-only-wrap img {
        width: 100%;
        height: auto;
        max-height: min(90vh, 920px);
        object-fit: contain;
        display: block;
        vertical-align: top;
    }

    .ps-content-image-only .ps-image-only-wrap a {
        display: block;
        line-height: 0;
    }

    .ps-content-image-only .ps-image-only-wrap a:hover img {
        opacity: 0.97;
    }

    /* ক্লোজ বাটন — উপরে ডানে, কালো X, সাদা বৃত্ত (রেফারেন্স স্টাইল) */
    .pop-banner-close,
    .ps-content-image-only .pop-banner-close {
        position: absolute;
        top: 6px;
        right: 6px;
        z-index: 1060;
        width: 36px;
        height: 36px;
        padding: 0;
        margin: 0;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.96) !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.18);
        color: #111 !important;
        font-size: 26px;
        line-height: 1;
        font-weight: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s, background 0.2s;
    }

    .pop-banner-close:hover,
    .ps-content-image-only .pop-banner-close:hover {
        background: #fff !important;
        color: #000 !important;
        transform: scale(1.06);
    }

    .pop-banner-close span {
        line-height: 0.85;
        margin-top: -2px;
    }

    /* টেক্সট+ইমেইজ লেআউটে ক্লোজ বাটন পজিশন */
    .ps-content:not(.ps-content-image-only) .pop-banner-close {
        top: 15px;
        right: 15px;
        width: 34px;
        height: 34px;
        font-size: 22px;
    }

    /* স্প্লিট লেআউট পপআপেও গোল কোণা */
    .pop-banner-modal .ps-content:not(.ps-content-image-only) {
        border-radius: 12px;
        overflow: hidden;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .ps-layout {
            flex-direction: column-reverse; /* Image Top, Text Bottom */
        }
        .ps-text-section { width: 100%; padding: 30px; }
        .ps-image-section { width: 100%; height: 250px; }
        .ps-brand { font-size: 30px; }
        .pop-banner-modal .modal-dialog.pop-banner-dialog {
            max-width: 96vw;
            margin: 0.5rem auto;
        }
        .ps-content-image-only .ps-image-only-wrap img {
            max-height: 88vh;
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ৩ ঘন্টা পর পর দেখাবে
        const hoursToWait = 3;  
        // সাইটে ঢোকার ২ সেকেন্ড পর দেখাবে
        const delayInSeconds = 2; 

        const timeLimit = hoursToWait * 60 * 60 * 1000;
        const lastShown = localStorage.getItem('popupLastShown');
        const now = new Date().getTime();

        if (!lastShown || (now - lastShown > timeLimit)) {
            setTimeout(function() {
                // Try opening with jQuery (Standard for Laravel themes)
                if (typeof jQuery != 'undefined') {
                    $('#popShopModal').modal('show');
                } 
                // Try opening with Bootstrap 5
                else if (typeof bootstrap != 'undefined') {
                    var myModal = new bootstrap.Modal(document.getElementById('popShopModal'));
                    myModal.show();
                }

                localStorage.setItem('popupLastShown', now);
            }, delayInSeconds * 1000);
        }
    });
</script>
@endif

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('show_order_limit_modal'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ডাইনামিক হোয়াটসঅ্যাপ নাম্বার (ডাটাবেস থেকে)
        var whatsappNumber = "{{ $contact->whatsapp ?? $contact->hotline ?? '8801519607646' }}"; 
        
        Swal.fire({
            title: '', 
            html: `
                <div class="custom-modal-content">
                    <div class="modal-header-custom">
                        <div class="header-left">
                            <i class="fas fa-exclamation-triangle header-icon"></i>
                            <span>Duplicate Order Detective Alert</span>
                        </div>
                        <i class="fas fa-times close-icon" onclick="Swal.close()"></i>
                    </div>

                    <div class="modal-body-custom">
                        <p>
                            <img src="https://img.icons8.com/emoji/48/000000/warning-emoji.png" style="width: 20px; vertical-align: text-bottom;"> 
                            <b>সতর্কতা!</b> আপনি ইতিমধ্যে এই পণ্যটির জন্য অর্ডার দিয়েছেন। নির্দিষ্ট সময়ের মধ্যে একই পণ্যের পুনরায় অর্ডার দেওয়া অনুমোদিত নয়। 
                            👉 আপনি যদি সত্যিই আবার অর্ডার করতে চান, তাহলে নিচে দেওয়া WhatsApp নম্বরে যোগাযোগ করুন:
                        </p>
                    </div>

                    <div class="modal-footer-custom">
                        <a href="https://wa.me/${whatsappNumber}?text=আমি একই পণ্য পুনরায় অর্ডার করতে চাই, অনুগ্রহ করে সাহায্য করুন।" target="_blank" class="btn-whatsapp-custom">
                            <i class="fab fa-whatsapp"></i> CONTACT ON WHATSAPP
                        </a>
                        <button onclick="Swal.close()" class="btn-close-custom">{{ __('Close') }}</button>
                    </div>
                </div>
            `,
            showConfirmButton: false, // ডিফল্ট বাটন বন্ধ রাখা হয়েছে
            background: 'transparent', // ডিফল্ট ব্যাকগ্রাউন্ড রিমুভ
            customClass: {
                popup: 'swal-no-padding'
            },
            allowOutsideClick: false
        });
    });
</script>

<style>
    /* পপ-আপ কন্টেইনার রিসেট */
    .swal-no-padding {
        padding: 0 !important;
        background: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    /* মেইন বক্স ডিজাইন */
    .custom-modal-content {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        font-family: 'Arial', sans-serif;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        max-width: 500px;
        margin: 0 auto;
    }

    /* লাল হেডার (ছবির মতো হুবহু) */
    .modal-header-custom {
        background-color: #b91c1c; /* গাঢ় লাল */
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        font-size: 18px;
        font-weight: bold;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-icon {
        color: #facc15; /* হলুদ আইকন */
        font-size: 20px;
    }

    .close-icon {
        cursor: pointer;
        opacity: 0.8;
        font-size: 20px;
        transition: 0.2s;
    }
    .close-icon:hover {
        opacity: 1;
    }

    /* বডি টেক্সট */
    .modal-body-custom {
        padding: 30px 25px;
        text-align: left;
        font-size: 15px;
        line-height: 1.6;
        color: #4b5563;
    }

    /* ফুটার এবং বাটন */
    .modal-footer-custom {
        padding: 0 25px 30px 25px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    /* হোয়াটসঅ্যাপ বাটন (সবুজ) */
    .btn-whatsapp-custom {
        background-color: #10b981;
        color: white !important;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: background 0.3s;
        border: none;
    }
    .btn-whatsapp-custom:hover {
        background-color: #059669;
    }

    /* ক্লোজ বাটন (লাল) */
    .btn-close-custom {
        background-color: #dc2626;
        color: white;
        padding: 10px 30px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 14px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: background 0.3s;
    }
    .btn-close-custom:hover {
        background-color: #b91c1c;
    }

    /* রেস্পন্সিভ ডিজাইন */
    @media (max-width: 450px) {
        .modal-footer-custom {
            flex-direction: column;
        }
        .btn-whatsapp-custom, .btn-close-custom {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endif


<script>
/* ЁЯФ╣ Toggle Open/Close */
document.getElementById("chatToggle").addEventListener("click", function() {
  document.getElementById("chatOptions").classList.toggle("show");
});
</script>


        <!-- /. fixed sidebar -->

        <div id="custom-modal"></div>
        <div id="page-overlay"></div>
        <div id="loading"><div class="custom-loader"></div></div>

        <script src="{{asset('public/frontEnd/js/jquery-3.6.3.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/bootstrap.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/owl.carousel.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wsit-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu-init.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wow.min.js')}}"></script>
        <script>
            new WOW().init();
        </script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <!-- feather icon -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
        <script>
            feather.replace();
        </script>
        <script src="{{asset('public/backEnd/')}}/assets/js/toastr.min.js"></script>
        {!! Toastr::message() !!} @stack('script')
        <script>
            $(".quick_view").on("click", function () {
                var id = $(this).data("id");
                $("#loading").show();
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('quickview')}}",
                        success: function (data) {
                            if (data) {
                                $("#custom-modal").html(data);
                                $("#custom-modal").show();
                                $("#loading").hide();
                                $("#page-overlay").show();
                            }
                        },
                    });
                }
            });
        </script>
        <!-- quick view end -->
        <!-- cart js start -->
        <script>
            function runFlyToCart($sourceEl, onComplete) {
                var $flyImg;
                if ($sourceEl && $sourceEl.closest && $sourceEl.closest('.variant-modal-content').length)
                    $flyImg = $sourceEl.closest('.variant-modal-content').find('.variant-modal-img img').first();
                if (!$flyImg || !$flyImg.length)
                    $flyImg = ($sourceEl && $sourceEl.closest) ? $sourceEl.closest('.product_item, .wist_item, .search-item, .quick-product, .product-section, .main-details-page, .details-action-box, .block__single, form').find('.pro_img img, .quick-product-img img, .details_slider img, .block__pic, .dimage_item img').first() : null;
                if (!$flyImg || !$flyImg.length) $flyImg = $('.product_item .pro_img img, .wist_item .pro_img img').first();
                if (!$flyImg || !$flyImg.length) $flyImg = $('.details_slider img, .block__pic, .dimage_item img, #details_slider_main img').first();
                if (!$flyImg.length) { if (typeof onComplete === 'function') onComplete(); return; }
                var rect = $flyImg[0].getBoundingClientRect();
                var $clone = $flyImg.clone().addClass('fly-to-cart-img').css({
                    position: 'fixed', width: 90, height: 110,
                    left: rect.left, top: rect.top, margin: 0, padding: 0, zIndex: 99999
                }).appendTo('body');
                var $target = $('#floatingCartBtn, .floating-cart-widget').first();
                if (!$target.length || !$target.is(':visible')) $target = $('.mobile_bottom_nav .cart_badge').closest('a').first();
                if (!$target.length) $target = $('.menu-bag a').first();
                var destRect = $target.length && $target.is(':visible') ? $target[0].getBoundingClientRect() : { left: $(window).width() - 60, top: $(window).height() / 2 - 40 };
                var endW = 36, endH = 44;
                var endLeft = destRect.left + ($target.length ? (destRect.width || 0) / 2 - endW / 2 : 0);
                var endTop = destRect.top + ($target.length ? (destRect.height || 0) / 2 - endH / 2 : 0);
                var midLeft = (rect.left + endLeft) / 2 - 20;
                var midTop = Math.min(rect.top, endTop) - 100;
                $clone.animate({ left: midLeft, top: midTop, width: 70, height: 85, opacity: 1 }, 300, 'swing', function() {
                    $(this).animate({ left: endLeft, top: endTop, width: endW, height: endH, opacity: 0.6 }, 350, 'swing', function() {
                        $clone.remove();
                        if ($target && $target.length) { $target.addClass('cart-bump-animate'); setTimeout(function() { $target.removeClass('cart-bump-animate'); }, 450); }
                        if (typeof onComplete === 'function') onComplete();
                    });
                });
            }
            function fireAddToCartEvent(el, id, qty, name, price, category) {
                qty = parseInt(qty) || 1;
                price = parseFloat(price) || 0;
                var item = { item_id: String(id), item_name: name || '', price: price, quantity: qty, item_category: category || '' };
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ event: 'add_to_cart', ecommerce: { currency: 'BDT', value: price * qty, items: [item] } });
                if (typeof fbq === 'function') fbq('track', 'AddToCart', { content_ids: [String(id)], content_name: name, content_type: 'product', value: price * qty, currency: 'BDT', num_items: qty });
                if (typeof ttq !== 'undefined') ttq.track('AddToCart', { content_type: 'product', content_id: String(id), content_name: name, value: price * qty, currency: 'BDT', quantity: qty });
            }
            $(document).on("click", ".addcartbutton", function (e) {
                e.preventDefault();
                var $btn = $(this);
                var id = $btn.data("id");
                var qty = 1;
                var name = $btn.data("name") || $btn.closest('.product_item, .wist_item, .search-item').find('.pro_name a, .product-name a').first().text().trim();
                var price = parseFloat($btn.data("price")) || parseFloat($btn.closest('.product_item, .wist_item, .search-item').find('.pro_price span, .pro_price p, .price').first().text().replace(/[^\d.]/g, '')) || 0;
                var category = $btn.data("category") || '';
                if (id) {
                    $.ajax({
                        cache: "false",
                        type: "GET",
                        url: "{{url('add-to-cart')}}/" + id + "/" + qty,
                        dataType: "json",
                        success: function (data) {
                            if (data) {
                                fireAddToCartEvent(null, id, qty, name, price, category);
                                toastr.success('কার্টে যোগ হয়েছে!', 'সফল');
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                                runFlyToCart($btn, function() { if (typeof openSidebarCart === "function") openSidebarCart(); });
                            }
                        },
                    });
                }
            });
            /* Form submit interceptor - কার্ট ফর্ম সাবমিট হলে AJAX দিয়ে পাঠাবে, পেজ রিলোড হবে না */
            $(document).on("submit", "form.ajax-cart-form:not(#productDetailsCartForm), form[action*='cart/store']:not(#productDetailsCartForm)", function (e) {
                var $form = $(this);
                if ($form.hasClass('cart-ajax-submit')) return;
                e.preventDefault();
                var id = $form.find("input[name=id]").val();
                var qty = $form.find("input[name=qty]").val() || 1;
                var hasOrderNow = false;
                if (e.originalEvent && e.originalEvent.submitter && e.originalEvent.submitter.name === 'order_now') {
                    hasOrderNow = true;
                } else {
                    hasOrderNow = !!$form.find("input[name=order_now]").val();
                }
                var name = $form.closest('.product_item, .wist_item, .search-item').find('.pro_name a, .product-name a').first().text().trim();
                if (!name) name = $form.closest('.details-action-box, .product-details-area, .block__single').find('h1, h2, .product-title').first().text().trim() || '';
                var priceText = $form.closest('.product_item, .wist_item').find('.pro_price p, .pro_price span').first().text();
                if (!priceText) priceText = $form.closest('.details-action-box, .block__single').find('.details-price, .block__price, .price').first().text() || '';
                var price = parseFloat(String(priceText).replace(/[^\d.]/g, '')) || 0;
                var category = $form.closest('.product_item, .wist_item').find('[data-category]').data('category') || '';
                $form.addClass('cart-ajax-submit');
                $.ajax({
                    type: "POST",
                    data: $form.serialize(),
                    url: $form.attr('action'),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (data) {
                        if (data && data.success) {
                            fireAddToCartEvent(null, id, qty, name, price, category);
                            toastr.success('কার্টে যোগ হয়েছে!', 'সফল');
                            cart_count();
                            mobile_cart();
                            if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            var checkoutUrl = (data.redirect) ? data.redirect : "{{ route('customer.checkout') }}";
                            if (data.redirect || hasOrderNow) {
                                window.location.href = checkoutUrl;
                            } else {
                                runFlyToCart($form, function() { if (typeof openSidebarCart === "function") openSidebarCart(); });
                            }
                        } else {
                            $form[0].submit();
                        }
                    },
                    error: function() {
                        $form[0].submit();
                    },
                    complete: function() { $form.removeClass('cart-ajax-submit'); }
                });
                return false;
            });
            $(document).on("click", ".cart_store", function (e) {
                var $btn = $(this);
                var $form = $btn.closest('form');
                if ($form.length && $form.attr('action') && !$form.hasClass('cart-ajax-submit')) {
                    var id = $btn.data("id") || $form.find("input[name=id]").val();
                    var qty = $form.find("input[name=qty]").val() || 1;
                    var name = $btn.data("name") || $form.closest('.product_item, .wist_item, .product-cart, .quick-view-content, .quick-product').find('.name, .pro_name a').first().text().trim();
                    var priceText = $form.closest('.product_item, .wist_item, .product-cart, .quick-view-content, .quick-product').find('.details-price, .pro_price span').first().text();
                    var price = parseFloat($btn.data("price")) || parseFloat(String(priceText).replace(/[^\d.]/g, '')) || 0;
                    var category = $btn.data("category") || $form.closest('.quick-product').find('.delivery_details td').first().text().replace('Category:','').trim() || '';
                    if (id) {
                        e.preventDefault();
                        $form.addClass('cart-ajax-submit');
                        $.ajax({
                            type: "POST",
                            data: $form.serialize(),
                            url: $form.attr('action'),
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            success: function (data) {
                                if (data && data.success) {
                                    fireAddToCartEvent(null, id, qty, name, price, category);
                                    toastr.success('কার্টে যোগ হয়েছে!', 'সফল');
                                    cart_count();
                                    mobile_cart();
                                    if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                                    runFlyToCart($btn, function() { if (typeof openSidebarCart === "function") openSidebarCart(); });
                                } else {
                                    $form[0].submit();
                                }
                            },
                            error: function() {
                                $form[0].submit();
                            },
                            complete: function() { $form.removeClass('cart-ajax-submit'); }
                        });
                    }
                }
            });

            $(document).on("click", ".cart_remove", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.remove')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                cart_summary();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            $(document).on("click", ".cart_increment", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.increment')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            $(document).on("click", ".cart_decrement", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.decrement')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            function cart_count() {
                $.ajax({
                    type: "GET",
                    url: "{{route('cart.count')}}",
                    success: function (data) {
                        if (data) {
                            $("#cart-qty").html(data);
                        } else {
                            $("#cart-qty").empty();
                        }
                    },
                });
            }
            function mobile_cart() {
                $.ajax({
                    type: "GET",
                    url: "{{route('mobile.cart.count')}}",
                    success: function (data) {
                        if (data) {
                            $(".mobilecart-qty").html(data);
                        } else {
                            $(".mobilecart-qty").empty();
                        }
                    },
                });
            }
            function cart_summary() {
                $.ajax({
                    type: "GET",
                    url: "{{route('shipping.charge')}}",
                    dataType: "html",
                    success: function (response) {
                        $(".cart-summary").html(response);
                    },
                });
            }
        </script>
        <!-- cart js end -->
        <script>
            $(".search_click").on("keyup change", function () {
                var keyword = $(".search_keyword").val();
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function (products) {
                        if (products) {
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            });
            $(".msearch_click").on("keyup change", function () {
                var keyword = $(".msearch_keyword").val();
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function (products) {
                        if (products) {
                            $("#loading").hide();
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            });
        </script>
        <!-- search js start -->
        <script></script>
        <script></script>
        <script>
            $(".district").on("change", function () {
                var id = $(this).val();
                $.ajax({
                    type: "GET",
                    data: { id: id },
                    url: "{{route('districts')}}",
                    success: function (res) {
                        if (res) {
                            $(".area").empty();
                            $(".area").append('<option value="">Select..</option>');
                            $.each(res, function (key, value) {
                                $(".area").append('<option value="' + key + '" >' + value + "</option>");
                            });
                        } else {
                            $(".area").empty();
                        }
                    },
                });
            });
        </script>
        <script>
            $(".toggle").on("click", function () {
                $("#page-overlay").show();
                $(".mobile-menu").addClass("active");
            });

            $("#page-overlay").on("click", function () {
                $("#page-overlay").hide();
                $(".mobile-menu").removeClass("active");
                $(".feature-products").removeClass("active");
            });

            $(".mobile-menu-close").on("click", function () {
                $("#page-overlay").hide();
                $(".mobile-menu").removeClass("active");
            });

            $(".mobile-filter-toggle").on("click", function () {
                $("#page-overlay").show();
                $(".feature-products").addClass("active");
            });
        </script>
        <script>
            $(document).ready(function () {
                $(".parent-category").each(function () {
                    const menuCatToggle = $(this).find(".menu-category-toggle");
                    const secondNav = $(this).find(".second-nav");

                    menuCatToggle.on("click", function () {
                        menuCatToggle.toggleClass("active");
                        secondNav.slideToggle("fast");
                        $(this).closest(".parent-category").toggleClass("active");
                    });
                });
                $(".parent-subcategory").each(function () {
                    const menuSubcatToggle = $(this).find(".menu-subcategory-toggle");
                    const thirdNav = $(this).find(".third-nav");

                    menuSubcatToggle.on("click", function () {
                        menuSubcatToggle.toggleClass("active");
                        thirdNav.slideToggle("fast");
                        $(this).closest(".parent-subcategory").toggleClass("active");
                    });
                });
            });
        </script>

        <script>
            // MmenuLight শুধুমাত্র চালাবে যখন #menu element থাকে (null check)
            var menuEl = document.querySelector("#menu");
            var menuLink = document.querySelector('a[href="#menu"]');
            if (menuEl && menuLink) {
                var menu = new MmenuLight(menuEl, "all");
                var navigator = menu.navigation({
                    selectedClass: "Selected",
                    slidingSubmenus: true,
                    title: "ক্যাটাগরি",
                });
                var drawer = menu.offcanvas({});
                menuLink.addEventListener("click", function(evnt) {
                    evnt.preventDefault();
                    drawer.open();
                });
            }
        </script>

        <script>
            // document.addEventListener("DOMContentLoaded", function () {
            //     window.addEventListener("scroll", function () {
            //         if (window.scrollY > 200) {
            //             document.getElementById("navbar_top").classList.add("fixed-top");
            //         } else {
            //             document.getElementById("navbar_top").classList.remove("fixed-top");
            //             document.body.style.paddingTop = "0";
            //         }
            //     });
            // });
            /*=== Main Menu Fixed === */
            // document.addEventListener("DOMContentLoaded", function () {
            //     window.addEventListener("scroll", function () {
            //         if (window.scrollY > 0) {
            //             document.getElementById("m_navbar_top").classList.add("fixed-top");
            //             // add padding top to show content behind navbar
            //             navbar_height = document.querySelector(".navbar").offsetHeight;
            //             document.body.style.paddingTop = navbar_height + "px";
            //         } else {
            //             document.getElementById("m_navbar_top").classList.remove("fixed-top");
            //             // remove padding top from body
            //             document.body.style.paddingTop = "0";
            //         }
            //     });
            // });
            /*=== Main Menu Fixed === */

            $(window).scroll(function () {
                if ($(this).scrollTop() > 50) {
                    $(".scrolltop:hidden").stop(true, true).fadeIn();
                } else {
                    $(".scrolltop").stop(true, true).fadeOut();
                }
            });
            $(function () {
                $(".scroll").click(function () {
                    $("html,body").animate({ scrollTop: $(".gotop").offset().top }, "1000");
                    return false;
                });
            });
        </script>
        <script>
            $(".filter_btn").click(function(){
               $(".filter_sidebar").addClass('active');
               $("body").css("overflow-y", "hidden");
            })
            $(".filter_close").click(function(){
               $(".filter_sidebar").removeClass('active');
               $("body").css("overflow-y", "auto");
            })
        </script>
        <!--search ANIMAtion end-->
        <!-- Google Tag Manager (noscript) -->
        @foreach($gtm_code as $gtm)
        @php $gtmId = str_starts_with($gtm->code, 'GTM-') ? $gtm->code : 'GTM-'.$gtm->code; @endphp
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endforeach
        <!-- End Google Tag Manager (noscript) -->
		
		
    </body>
</html>
