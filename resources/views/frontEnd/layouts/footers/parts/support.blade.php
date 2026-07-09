{{-- Support Links (Footer Component) --}}
<div class="footer-part-support" style="background:var(--footer-bg); color:var(--footer-text); padding:20px 0;">
    <div class="container">
        <h6 class="text-uppercase fw-bold mb-3" style="color:var(--footer-text);">{{ __('Support') }}</h6>
        <ul class="list-unstyled small mb-0">
            <li class="mb-1"><a href="{{ route('contact') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Contact Us') }}</a></li>
            <li class="mb-1"><a href="{{ route('complaint') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Complaints') }}</a></li>
            <li class="mb-1"><a href="{{ route('customer.order_track') }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('Track Order') }}</a></li>
            <li class="mb-1"><a href="#" class="text-decoration-none" style="color:var(--footer-text);opacity:0.7;">{{ __('FAQ') }}</a></li>
        </ul>
    </div>
</div>
