{{-- Default Header - Original complete working header --}}
<header id="navbar_top">
    {{-- Top Bar --}}
    @if(($generalsetting->header_top_bar ?? 1) == 1)
    <div class="top_header" style="background-color:var(--secondary-color)">
        <div class="container d-flex align-items-center">
            <a href="tel:{{ $contact->hotline ?? '01XXX-XXXXXX' }}" class="text-center bg-light px-2 d-none d-sm-block fw-bold fs-4" style="color:var(--primary-color,#13027D);min-width:270px;">
                <i class="fa-solid fa-headset"></i> {{ $contact->hotline ?? '01XXX-XXXXXX' }}
            </a>
            <div class="d-flex align-items-center flex-grow-1 overflow-hidden">
                <div class="headline-scroll-wrapper">
                    <span class="headline-scroll-text text-light fs-6">{{ $generalsetting->top_headline ?? '' }}</span>
                    <span class="headline-scroll-text headline-scroll-duplicate text-light fs-6" aria-hidden="true">{{ $generalsetting->top_headline ?? '' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Mobile Header --}}
    <div class="mobile-header sticky">
        <div class="mobile-logo">
            <div class="menu-bar"><a class="toggle"><i class="fa-solid fa-bars"></i></a></div>
            <div class="menu-logo">
                <a href="{{ route('home') }}"><img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.svg') }}" alt="" /></a>
            </div>
            <div class="menu-bag">
                <a href="{{ route('customer.checkout') }}" class="margin-shopping">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="mobilecart-qty">{{ Cart::instance('shopping')->count() }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Mobile Search --}}
    <div class="mobile-search">
        <form action="{{ route('search') }}">
            <input type="text" placeholder="Search Product ... " value="" class="msearch_keyword msearch_click search_keyword" name="keyword" />
            <button><i data-feather="search"></i></button>
        </form>
        <div class="search_result"></div>
    </div>

    {{-- Desktop Main Header --}}
    <div class="main-header" style="{{ ($generalsetting->header_sticky ?? 1) ? 'position:sticky;top:0;z-index:1020;' : '' }}">
        <div class="logo-area">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="logo-header">
                            <div class="main-logo">
                                <a href="{{ route('home') }}"><img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.svg') }}" alt="" /></a>
                            </div>
                            <div class="main-search">
                                <form action="{{ route('search') }}">
                                    <input type="text" placeholder="Search Product..." class="search_keyword search_click" name="keyword" />
                                    <button><i data-feather="search"></i></button>
                                </form>
                                <div class="search_result"></div>
                            </div>
                            <div class="header-list-items">
                                <ul>
                                    <li class="track_btn">
                                        <a href="{{ route('customer.order_track') }}"><i class="fa fa-truck"></i> {{ __('Track Order') }}</a>
                                    </li>
                                    <li class="track_btn lang-switch">
                                        <a href="{{ route('lang.switch', app()->getLocale() == 'en' ? 'bn' : 'en') }}" title="Switch Language">
                                            <i class="fa fa-language"></i> {{ app()->getLocale() == 'en' ? 'বাংলা' : 'English' }}
                                        </a>
                                    </li>
                                    @if(Auth::guard('customer')->user())
                                    <li class="for_order"><p><a href="{{ route('customer.account') }}"><i class="fa-regular fa-user"></i> {{ Str::limit(Auth::guard('customer')->user()->name, 14) }}</a></p></li>
                                    @else
                                    <li class="for_order"><p><a href="{{ route('customer.login') }}"><i class="fa-regular fa-user"></i> {{ __('Login / Sign Up') }}</a></p></li>
                                    @endif
                                    <li class="cart-dialog" id="cart-qty">
                                        <a href="{{ route('customer.checkout') }}">
                                            <p class="margin-shopping"><i class="fa-solid fa-cart-shopping"></i> <span>{{ Cart::instance('shopping')->count() }}</span></p>
                                        </a>
                                        <div class="cshort-summary">
                                            <ul>
                                                @foreach(Cart::instance('shopping')->content() as $key => $value)
                                                <li><a href=""><img src="{{ asset($value->options->image) }}" alt="" /></a></li>
                                                <li><a href="">{{ Str::limit($value->name, 30) }}</a></li>
                                                <li>Qty: {{ $value->qty }}</li>
                                                <li><p>৳{{ $value->price }}</p><button class="remove-cart cart_remove" data-id="{{ $value->rowId }}"><i data-feather="x"></i></button></li>
                                                @endforeach
                                            </ul>
                                            <p><strong>{{ __('Total') }} : ৳{{ Cart::instance('shopping')->subtotal() }}</strong></p>
                                            <a href="{{ route('customer.checkout') }}" class="go_cart">{{ __('Order Now') }}</a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Menu --}}
        <div class="menu-area">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="d-flex align-items-center" style="gap:16px;">
                            @includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'light'])
                            <div class="catagory_menu text-center flex-grow-1">
                            <ul>
                                @foreach($menucategories as $scategory)
                                <li class="cat_bar">
                                    <a href="{{ url('category/' . $scategory->slug) }}">
                                        <span class="cat_head">{{ $scategory->name }}</span>
                                        @if($scategory->subcategories->count() > 0) <i class="fa-solid fa-angle-down cat_down"></i> @endif
                                    </a>
                                    @if($scategory->subcategories->count() > 0)
                                    <ul class="Cat_menu">
                                        @foreach($scategory->subcategories as $subcat)
                                        <li class="Cat_list cat_list_hover">
                                            <a href="{{ url('subcategory/' . $subcat->slug) }}">
                                                <span>{{ Str::limit($subcat->subcategoryName, 25) }}</span>
                                                @if($subcat->childcategories->count() > 0) <i class="fa-solid fa-chevron-right cat_down"></i> @endif
                                            </a>
                                            @if($subcat->childcategories->count() > 0)
                                            <ul class="child_menu">
                                                @foreach($subcat->childcategories as $childcat)
                                                <li class="child_main"><a href="{{ url('products/' . $childcat->slug) }}">{{ $childcat->childcategoryName }}</a></li>
                                                @endforeach
                                            </ul>
                                            @endif
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
