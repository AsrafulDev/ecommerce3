{{-- Top Bar Component --}}
@if(($generalsetting->header_top_bar ?? 1) == 1)
<div class="header-top-bar" style="background: var(--secondary-color,#0b5ed7); color:#fff; padding:6px 0; font-size:13px;">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <a href="tel:{{ $contact->hotline ?? '' }}" class="text-white text-decoration-none small">
                <i class="fa-solid fa-phone"></i> {{ $contact->hotline ?? '' }}
            </a>
            <span class="d-none d-md-inline text-white-50 small">{{ $generalsetting->top_headline ?? '' }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @guest('customer')
                <a href="{{ route('customer.login') }}" class="text-white small text-decoration-none">{{ __('Login') }}</a>
                <span class="text-white-50">|</span>
                <a href="{{ route('customer.register') }}" class="text-white small text-decoration-none">{{ __('Register') }}</a>
            @endguest
            @auth('customer')
                <a href="{{ route('customer.account') }}" class="text-white small text-decoration-none">
                    <i class="fa-solid fa-user"></i> {{ __('My Account') }}</a>
            @endauth
        </div>
    </div>
</div>
@endif
