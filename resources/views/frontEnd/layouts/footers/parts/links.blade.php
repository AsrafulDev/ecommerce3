{{-- Quick Links (Footer Component) --}}
<div class="footer-part-links" style="background:var(--footer-bg); color:var(--footer-text); padding:20px 0;">
    <div class="container">
        <h6 class="text-uppercase fw-bold mb-3" style="color:var(--footer-text);">{{ __('Quick Links') }}</h6>
        <ul class="list-unstyled small mb-0">
            <li class="mb-1"><a href="{{ route('home') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Home') }}</a></li>
            <li class="mb-1"><a href="{{ route('shop') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">Shop</a></li>
            <li class="mb-1"><a href="{{ route('hotdeals') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Hot Deals') }}</a></li>
            <li class="mb-1"><a href="{{ route('brands') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Brands') }}</a></li>
            <li class="mb-1"><a href="{{ route('blogs') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Blog') }}</a></li>
        </ul>
    </div>
</div>
