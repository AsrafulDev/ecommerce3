{{-- Header Style 4: Centered Logo --}}
<header class="header-centered" id="mainHeader">
    <div class="container text-center py-3">
        <a href="{{ route('home') }}">
            <img src="{{ asset($generalsetting->dark_logo ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:50px;">
        </a>
        <div class="mt-2 d-none d-md-flex justify-content-center">
            <form action="{{ route('search') }}" method="GET" style="width:500px;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control rounded-pill" placeholder="Search..." style="background:#f5f5f5;border:none;">
                    <button class="btn rounded-circle ms-2" type="submit" style="width:40px;height:40px;background:var(--primary-color);color:#fff;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <nav class="border-top border-bottom">
        <div class="container">
            <ul class="d-flex justify-content-center list-unstyled mb-0 flex-wrap">
                <li><a href="{{ route('home') }}" class="d-block px-3 py-2 text-dark fw-semibold">Home</a></li>
                <li><a href="{{ route('shop') }}" class="d-block px-3 py-2 text-muted">Shop</a></li>
                <li><a href="{{ route('hotdeals') }}" class="d-block px-3 py-2 text-danger fw-semibold">Hot Deals</a></li>
                <li><a href="{{ route('flashsales') }}" class="d-block px-3 py-2 text-muted">Flash Sale</a></li>
                <li><a href="{{ route('brands') }}" class="d-block px-3 py-2 text-muted">Brands</a></li>
                <li><a href="{{ route('blog') }}" class="d-block px-3 py-2 text-muted">Blog</a></li>
            </ul>
        </div>
    </nav>
</header>
