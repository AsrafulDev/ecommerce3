{{-- Header Style 4: Centered Logo --}}
<header class="header-centered" id="mainHeader">
    <div class="container text-center py-3">
        <a href="{{ route('home') }}">
            <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.png' ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:50px;">
        </a>
        <div class="mt-2 d-none d-md-flex justify-content-center">
            <form action="{{ route('search') }}" method="GET" class="search-wrap" style="width:500px;position:relative;">
                <div class="input-group">
                    <input type="text" name="keyword" class="form-control rounded-pill search_keyword" placeholder="Search..." style="background:#f5f5f5;border:none;">
                    <button class="btn rounded-circle ms-2" type="submit" style="width:40px;height:40px;background:var(--primary-color);color:#fff;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <div class="search_result"></div>
            </form>
        </div>
    </div>
    <nav class="border-top border-bottom">
        <div class="container">
            <ul class="d-flex justify-content-center list-unstyled mb-0 flex-wrap align-items-center">
                <li class="d-flex align-items-center">@includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'light'])</li>
                <li><a href="{{ route('home') }}" class="d-block px-3 py-2 text-dark fw-semibold">{{ __('Home') }}</a></li>
                <li><a href="{{ route('shop') }}" class="d-block px-3 py-2 text-muted">Shop</a></li>
                <li><a href="{{ route('hotdeals') }}" class="d-block px-3 py-2 text-danger fw-semibold">{{ __('Hot Deals') }}</a></li>
                <li><a href="{{ route('flashsales') }}" class="d-block px-3 py-2 text-muted">{{ __('Flash Sale') }}</a></li>
                <li><a href="{{ route('brands') }}" class="d-block px-3 py-2 text-muted">{{ __('Brands') }}</a></li>
                <li><a href="{{ route('blogs') }}" class="d-block px-3 py-2 text-muted">{{ __('Blog') }}</a></li>
            </ul>
        </div>
    </nav>
</header>
