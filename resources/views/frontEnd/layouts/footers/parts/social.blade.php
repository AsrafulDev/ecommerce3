{{-- Social Icons (Footer Component) --}}
<div class="footer-part-social" style="background:var(--footer-bg); color:var(--footer-text); padding:20px 0;">
    <div class="container">
        <h6 class="text-uppercase fw-bold mb-3" style="color:var(--footer-text);">{{ __('Follow Us') }}</h6>
        <div class="d-flex gap-2 flex-wrap">
            @foreach(($socials ?? $socialicons ?? collect())->take(6) as $s)
                <a href="{{ $s->link ?? $s->url ?? '#' }}" target="_blank" rel="noopener"
                   class="d-flex align-items-center justify-content-center rounded-circle"
                   style="width:36px;height:36px;background:rgba(255,255,255,0.1);color:var(--footer-text);text-decoration:none;transition:all 0.3s;">
                    <i class="{{ $s->icon ?? 'fa-brands fa-facebook' }}"></i>
                </a>
            @endforeach
        </div>
    </div>
</div>
