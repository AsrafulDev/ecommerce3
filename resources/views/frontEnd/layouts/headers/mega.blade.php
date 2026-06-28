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
        </div>
    </div>
    <nav class="bg-dark">
        <div class="container">
            <ul class="d-flex list-unstyled mb-0">
                <li class="dropdown-mega position-relative">
                    <a href="#" class="d-block text-white px-4 py-3 text-decoration-none fw-bold" style="background:var(--primary-color);">
                        <i class="fa-solid fa-bars me-2"></i>All Categories
                    </a>
                    <div class="mega-dropdown position-absolute bg-white shadow-lg rounded" style="display:none;left:0;min-width:700px;z-index:999;">
                        <div class="row g-0 p-3">
                            @foreach(($menucategories ?? collect())->take(6) as $cat)
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('category',$cat->slug) }}" class="d-block fw-bold text-dark mb-1">{{ $cat->name }}</a>
                                @foreach(($cat->subcategories ?? [])->take(3) as $sub)
                                    <a href="{{ route('subcategory',$sub->slug) }}" class="d-block small text-muted ms-2">{{ $sub->subcategoryName }}</a>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                    </div>
                </li>
                <li><a href="{{ route('home') }}" class="d-block text-white px-3 py-3 text-decoration-none">Home</a></li>
                <li><a href="{{ route('shop') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">Shop</a></li>
                <li><a href="{{ route('hotdeals') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">Deals</a></li>
                <li><a href="{{ route('brands') }}" class="d-block text-white-50 px-3 py-3 text-decoration-none">Brands</a></li>
            </ul>
        </div>
    </nav>
</header>
@push('script')
<script>
document.querySelector('.dropdown-mega').addEventListener('mouseenter',function(){this.querySelector('.mega-dropdown').style.display='block'});
document.querySelector('.dropdown-mega').addEventListener('mouseleave',function(){this.querySelector('.mega-dropdown').style.display='none'});
</script>
@endpush
