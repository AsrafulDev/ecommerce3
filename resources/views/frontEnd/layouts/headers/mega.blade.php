{{-- Header Style 5: Mega Menu --}}
<header class="header-mega" id="mainHeader">
    <div class="bg-white border-bottom">
        <div class="container d-flex align-items-center py-2">
            <a href="{{ route('home') }}" class="me-4">
                <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.png' ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:40px;">
            </a>
            <form action="{{ route('search') }}" method="GET" class="flex-grow-1 me-3 d-none d-md-block">
                <div class="input-group"><input type="text" name="q" class="form-control bg-light border-0" placeholder="Search..."><button class="btn btn-dark" type="submit"><i class="fa-solid fa-search"></i></button></div>
            </form>
            <div class="d-flex gap-3">
                <a href="{{ route('customer.checkout') }}" class="position-relative text-dark"><i class="fa-solid fa-cart-shopping fs-5"></i><span class="cart-count-badge">{{ Cart::instance('shopping')->count() }}</span></a>
            </div>
            <div class="d-flex gap-3 ms-3">
                @auth
                    <a href="{{ route('customer.account') }}" class="text-dark"><i class="fa-solid fa-user fs-5"></i></a>
                @else
                    <a href="{{ route('login') }}" class="text-dark"><i class="fa-solid fa-right-to-bracket fs-5"></i></a>
                @endauth
            </div>
        </div>
    </div>
    <nav class="bg-dark">
        <div class="container">
            <ul class="d-flex list-unstyled mb-0 align-items-center">
                <li class="d-flex align-items-center me-2">
                    @includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'light'])
                </li>
                <li><a href="{{ route('home') }}" class="d-block text-white px-3 py-3 text-decoration-none">{{ __('Home') }}</a></li>
                <li><a href="{{ route('shop') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">Shop</a></li>
                <li><a href="{{ route('hotdeals') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">Deals</a></li>
                <li><a href="{{ route('brands') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">{{ __('Brands') }}</a></li>
            </ul>
        </div>
    </nav>
</header>
