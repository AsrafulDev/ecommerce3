{{-- {{ __('Footer Style') }} 3: {{ __('Dark') }} --}}
<footer class="bg-dark text-white">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4"><h5 class="fw-bold mb-3">{{ $generalsetting->name ?? '{{ __('Store') }}' }}</h5><p class="small text-secondary">{{ $generalsetting->footer_about_text ?? '' }}</p></div>
            <div class="col-lg-8"><div class="row"><div class="col-6 col-md-3"><h6 class="text-uppercase small mb-3">{{ __('{{ __('Link') }}s') }}</h6><ul class="list-unstyled small text-secondary"><li class="mb-1"><a href="{{ route('home') }}" class="text-secondary text-decoration-none">{{ __('Home') }}</a></li><li class="mb-1"><a href="{{ route('shop') }}" class="text-secondary text-decoration-none">{{ __('Shop') }}</a></li><li class="mb-1"><a href="{{ route('brands') }}" class="text-secondary text-decoration-none">{{ __('Brands') }}</a></li></ul></div><div class="col-6 col-md-3"><h6 class="text-uppercase small mb-3">{{ __('Support') }}</h6><ul class="list-unstyled small text-secondary"><li class="mb-1"><a href="{{ route('contact') }}" class="text-secondary text-decoration-none">{{ __('Contact') }}</a></li><li class="mb-1"><a href="#" class="text-secondary text-decoration-none">{{ __('FAQ') }}</a></li></ul></div><div class="col-md-6"><h6 class="text-uppercase small mb-3">{{ __('{{ __('New') }}sletter') }}</h6><div class="input-group input-group-sm"><input type="email" class="form-control bg-transparent border-secondary text-white" placeholder="{{ __('Email') }}"><button class="btn btn-light btn-sm">{{ __('Subscribe') }}</button></div></div></div></div>
        </div>
        <hr class="border-secondary">
        <div class="text-center small text-secondary">{{ $generalsetting->copyright ?? '© 2026' }}</div>
    </div>
</footer>

{{-- {{ __('Footer Style') }} 4: Minimal --}}
@if(false){{-- template reference only; rendered inline below --}}
<footer class="bg-white border-top text-center py-4">
    <div class="container">
        <p class="small text-muted mb-2">{{ $generalsetting->copyright ?? '© 2026' }}</p>
        <div class="d-flex justify-content-center gap-3 small">
            <a href="{{ route('home') }}" class="text-muted text-decoration-none">{{ __('Home') }}</a>
            <a href="{{ route('shop') }}" class="text-muted text-decoration-none">{{ __('Shop') }}</a>
            <a href="{{ route('contact') }}" class="text-muted text-decoration-none">{{ __('Contact') }}</a>
            <a href="#" class="text-muted text-decoration-none">{{ __('Privacy') }}</a>
        </div>
    </div>
</footer>
@endif
