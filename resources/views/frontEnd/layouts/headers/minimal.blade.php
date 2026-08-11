{{-- Header Style 3: Minimal --}}
<style>
.header-minimal .minimal-nav a { color:#555; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; }
.header-minimal .minimal-nav a:hover { color:var(--primary-color); }
</style>
<header class="header-minimal py-3 bg-white border-bottom" id="mainHeader">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="{{ route('home') }}">
            <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.png' ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:35px;">
        </a>
        <nav class="minimal-nav d-none d-md-flex gap-4 align-items-center">
            @includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'light'])
            <a href="{{ route('home') }}" class="text-decoration-none fw-bold">{{ __('Home') }}</a>
            <a href="{{ route('shop') }}" class="text-decoration-none">Shop</a>
            <a href="{{ route('hotdeals') }}" class="text-decoration-none">Deals</a>
            <a href="{{ route('brands') }}" class="text-decoration-none">{{ __('Brands') }}</a>
            <a href="{{ route('blogs') }}" class="text-decoration-none">{{ __('Blog') }}</a>
        </nav>
        <div class="d-flex gap-3 align-items-center">
            <button class="btn btn-link text-dark p-0 d-md-none" onclick="document.querySelector('.minimal-nav').classList.toggle('d-none')">
                <i class="fa-solid fa-bars fs-5"></i>
            </button>
            <form action="{{ route('search') }}" method="GET" class="d-none d-sm-block search-wrap" style="position:relative;">
                <div class="input-group input-group-sm"><input type="text" name="keyword" class="form-control border-0 bg-light rounded-pill search_keyword" placeholder="Search..." style="width:180px;"></div>
                <div class="search_result"></div>
            </form>
            <a href="{{ route('customer.checkout') }}" class="position-relative text-dark">
                <i class="fa-solid fa-bag-shopping fs-5"></i>
                <span class="cart-count-badge">{{ Cart::instance('shopping')->count() }}</span>
            </a>
        </div>
    </div>
</header>
