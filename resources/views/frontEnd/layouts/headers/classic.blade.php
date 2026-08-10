{{-- Header Style 1: Classic (Default) --}}
<header class="header-classic" id="mainHeader">
    {{-- Top Bar --}}
    @if(($generalsetting->header_top_bar ?? 1) == 1)
    <div class="header-top-bar" style="background: var(--secondary-color,#0b5ed7);">
        <div class="container d-flex justify-content-between align-items-center py-1">
            <div class="d-flex gap-3">
                <a href="tel:{{ $contact->hotline ?? '01XXX-XXXXXX' }}" class="text-white small text-decoration-none">
                    <i class="fa-solid fa-phone"></i> {{ $contact->hotline ?? '01XXX-XXXXXX' }}
                </a>
                <span class="text-white-50 small d-none d-md-inline">{{ $generalsetting->top_headline ?? '' }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                @guest('customer')
                    <a href="{{ route('customer.login') }}" class="text-white small text-decoration-none">{{ __('Login') }}</a>
                    <span class="text-white-50">|</span>
                    <a href="{{ route('customer.register') }}" class="text-white small text-decoration-none">{{ __('Register') }}</a>
                @endguest
                @auth('customer')
                    <a href="{{ route('customer.account') }}" class="text-white small text-decoration-none">
                        <i class="fa-solid fa-user"></i>{{ __('My Account') }}</a>
                @endauth
            </div>
        </div>
    </div>
    @endif

    {{-- Main Header --}}
    <div class="header-main" style="background:#fff; {{ ($generalsetting->header_sticky ?? 1) ? 'position:sticky;top:0;z-index:1020;' : '' }}">
        <div class="container">
            <div class="row align-items-center py-2">
                {{-- Logo --}}
                <div class="col-lg-2 col-md-3 col-6">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.png' ?? 'public/assets/images/CurlBazar.png') }}" 
                             alt="Logo" style="max-height:45px;">
                    </a>
                </div>

                {{-- Search --}}
                <div class="col-lg-5 col-md-6 d-none d-md-block">
                    <form action="{{ route('search') }}" method="GET" class="header-search-form">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="{{ __('Search products...') }}" 
                                   style="border-radius: 25px 0 0 25px; border: 2px solid var(--primary-color);">
                            <button class="btn text-white" type="submit" 
                                    style="background:var(--primary-color); border-radius: 0 25px 25px 0;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Cart & Wishlist --}}
                <div class="col-lg-5 col-md-3 col-6 text-end">
                    <div class="d-flex justify-content-end align-items-center gap-3">
                        <a href="{{ route('customer.account') }}" class="text-dark position-relative" title="Account/Wishlist">
                            <i class="fa-regular fa-heart fs-5"></i>
                        </a>
                        <a href="{{ route('customer.checkout') }}" class="text-dark position-relative" title="Cart">
                            <i class="fa-solid fa-bag-shopping fs-5"></i>
                            <span class="cart-count-badge">{{ Cart::instance('shopping')->count() }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="header-nav" style="background:var(--primary-color);">
            <div class="container">
                <ul class="nav-list d-flex list-unstyled mb-0 align-items-center">
                    <li class="d-flex align-items-center me-2">@includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'dark'])</li>
                    <li><a href="{{ route('home') }}" class="text-white px-3 py-2 d-block">{{ __('Home') }}</a></li>
                    <li><a href="{{ route('shop') }}" class="text-white px-3 py-2 d-block">Shop</a></li>
                    <li><a href="{{ route('hotdeals') }}" class="text-white px-3 py-2 d-block">{{ __('Hot Deals') }}</a></li>
                    <li><a href="{{ route('flashsales') }}" class="text-white px-3 py-2 d-block">{{ __('Flash Sale') }}</a></li>
                    <li><a href="{{ route('brands') }}" class="text-white px-3 py-2 d-block">{{ __('Brands') }}</a></li>
                    <li><a href="{{ route('blogs') }}" class="text-white px-3 py-2 d-block">{{ __('Blog') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="text-white px-3 py-2 d-block">Contact</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>
