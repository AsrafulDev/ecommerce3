{{-- Header Style 2: Modern --}}
<header class="header-modern" id="mainHeader">
    <div class="container">
        <div class="row align-items-center py-2">
            <div class="col-lg-2 col-6">
                <a href="{{ route('home') }}">
                    <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.png' ?? 'public/assets/images/CurlBazar.png') }}" 
                         alt="Logo" style="max-height:40px;">
                </a>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <form action="{{ route('search') }}" method="GET">
                    <div class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control border-0 bg-light" placeholder="What are you looking for?" style="border-radius:30px 0 0 30px;">
                        <button class="btn px-4 text-white" type="submit" style="background:var(--primary-color);border-radius:0 30px 30px 0;">
                            <i class="fa-solid fa-magnifying-glass"></i>{{ __('Search') }}</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-4 col-6 text-end">
                <div class="d-flex justify-content-end align-items-center gap-3">
                    @guest('customer')
                        <a href="{{ route('customer.login') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">{{ __('Login') }}</a>
                    @endguest
                    <a href="{{ route('customer.checkout') }}" class="position-relative">
                        <i class="fa-solid fa-cart-shopping fs-4 text-dark"></i>
                        <span class="cart-count-badge">{{ Cart::instance('shopping')->count() }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <nav style="border-top:1px solid #eee;border-bottom:2px solid var(--primary-color);">
        <div class="container">
            <ul class="d-flex list-unstyled mb-0 gap-1">
                <li><a href="{{ route('home') }}" class="d-block px-3 py-2 text-dark fw-semibold">{{ __('Home') }}</a></li>
                <li><a href="{{ route('shop') }}" class="d-block px-3 py-2 text-dark">Shop</a></li>
                <li><a href="{{ route('category','smartphones') }}" class="d-block px-3 py-2 text-dark">{{ __('Categories') }}</a></li>
                <li><a href="{{ route('hotdeals') }}" class="d-block px-3 py-2 text-dark">Deals</a></li>
                <li><a href="{{ route('brands') }}" class="d-block px-3 py-2 text-dark">{{ __('Brands') }}</a></li>
            </ul>
        </div>
    </nav>
</header>
