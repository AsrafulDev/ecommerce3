{{-- Cart & Icons Component --}}
<div class="header-icons-area" style="background:#fff; padding:8px 0;">
    <div class="container">
        <div class="d-flex justify-content-end align-items-center gap-3">
            <a href="{{ route('customer.order_track') }}" class="text-dark text-decoration-none small" title="{{ __('Track Order') }}">
                <i class="fa fa-truck"></i> {{ __('Track Order') }}
            </a>
            @if(Auth::guard('customer')->user())
                <a href="{{ route('customer.account') }}" class="text-dark text-decoration-none small" title="{{ __('My Account') }}">
                    <i class="fa-regular fa-user"></i> {{ Str::limit(Auth::guard('customer')->user()->name, 14) }}
                </a>
            @else
                <a href="{{ route('customer.login') }}" class="text-dark text-decoration-none small" title="{{ __('Login') }}">
                    <i class="fa-regular fa-user"></i> {{ __('Login / Sign Up') }}
                </a>
            @endif
            <a href="{{ route('customer.checkout') }}" class="text-dark position-relative" title="{{ __('Cart') }}">
                <i class="fa-solid fa-cart-shopping fs-5"></i>
                <span class="badge bg-danger rounded-pill" style="position:absolute;top:-8px;right:-10px;font-size:10px;">
                    {{ Cart::instance('shopping')->count() }}
                </span>
            </a>
        </div>
    </div>
</div>
