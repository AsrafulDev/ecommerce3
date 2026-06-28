{{-- Footer Style 3: Dark --}}
<footer class="bg-dark text-white">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4"><h5 class="fw-bold mb-3">{{ $generalsetting->name ?? 'Store' }}</h5><p class="small text-secondary">{{ $generalsetting->footer_about_text ?? '' }}</p></div>
            <div class="col-lg-8"><div class="row"><div class="col-6 col-md-3"><h6 class="text-uppercase small mb-3">Links</h6><ul class="list-unstyled small text-secondary"><li class="mb-1"><a href="{{ route('home') }}" class="text-secondary text-decoration-none">Home</a></li><li class="mb-1"><a href="{{ route('shop') }}" class="text-secondary text-decoration-none">Shop</a></li><li class="mb-1"><a href="{{ route('brands') }}" class="text-secondary text-decoration-none">Brands</a></li></ul></div><div class="col-6 col-md-3"><h6 class="text-uppercase small mb-3">Support</h6><ul class="list-unstyled small text-secondary"><li class="mb-1"><a href="{{ route('contact') }}" class="text-secondary text-decoration-none">Contact</a></li><li class="mb-1"><a href="#" class="text-secondary text-decoration-none">FAQ</a></li></ul></div><div class="col-md-6"><h6 class="text-uppercase small mb-3">Newsletter</h6><div class="input-group input-group-sm"><input type="email" class="form-control bg-transparent border-secondary text-white" placeholder="Email"><button class="btn btn-light btn-sm">Subscribe</button></div></div></div></div>
        </div>
        <hr class="border-secondary">
        <div class="text-center small text-secondary">{{ $generalsetting->copyright ?? '© 2026' }}</div>
    </div>
</footer>

{{-- Footer Style 4: Minimal --}}
@if(false){{-- template reference only; rendered inline below --}}
<footer class="bg-white border-top text-center py-4">
    <div class="container">
        <p class="small text-muted mb-2">{{ $generalsetting->copyright ?? '© 2026' }}</p>
        <div class="d-flex justify-content-center gap-3 small">
            <a href="{{ route('home') }}" class="text-muted text-decoration-none">Home</a>
            <a href="{{ route('shop') }}" class="text-muted text-decoration-none">Shop</a>
            <a href="{{ route('contact') }}" class="text-muted text-decoration-none">Contact</a>
            <a href="#" class="text-muted text-decoration-none">Privacy</a>
        </div>
    </div>
</footer>
@endif
