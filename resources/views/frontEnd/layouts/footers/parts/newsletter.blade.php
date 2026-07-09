{{-- Newsletter (Footer Component) --}}
<div class="footer-part-newsletter" style="background:var(--footer-bg); color:var(--footer-text); padding:20px 0;">
    <div class="container">
        <h6 class="text-uppercase fw-bold mb-3" style="color:var(--footer-text);">{{ __('Newsletter') }}</h6>
        <p class="small mb-2" style="opacity:0.7;">{{ __('Subscribe for exclusive offers & updates.') }}</p>
        <form action="{{ route('frontend.newsletter.subscribe') }}" method="POST" class="d-flex gap-2">
            @csrf
            <input type="email" name="email" class="form-control form-control-sm" placeholder="{{ __('Your email') }}" required 
                   style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:var(--footer-text);">
            <button class="btn btn-sm text-white" type="submit" style="background:var(--primary-color);">{{ __('Subscribe') }}</button>
        </form>
    </div>
</div>
