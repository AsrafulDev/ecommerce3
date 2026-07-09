{{-- Navigation Menu Component --}}
<nav class="header-nav" style="background:var(--primary-color);">
    <div class="container">
        <ul class="d-flex list-unstyled mb-0 justify-content-center flex-wrap">
            <li><a href="{{ route('home') }}" class="text-white px-3 py-2 d-block text-decoration-none fw-semibold">{{ __('Home') }}</a></li>
            <li><a href="{{ route('shop') }}" class="text-white px-3 py-2 d-block text-decoration-none">Shop</a></li>
            <li><a href="{{ route('hotdeals') }}" class="text-white px-3 py-2 d-block text-decoration-none">{{ __('Hot Deals') }}</a></li>
            <li><a href="{{ route('flashsales') }}" class="text-white px-3 py-2 d-block text-decoration-none">{{ __('Flash Sale') }}</a></li>
            <li><a href="{{ route('brands') }}" class="text-white px-3 py-2 d-block text-decoration-none">{{ __('Brands') }}</a></li>
            <li><a href="{{ route('blogs') }}" class="text-white px-3 py-2 d-block text-decoration-none">{{ __('Blog') }}</a></li>
            <li><a href="{{ route('contact') }}" class="text-white px-3 py-2 d-block text-decoration-none">Contact</a></li>
        </ul>
    </div>
</nav>
