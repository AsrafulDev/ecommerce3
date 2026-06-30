{{-- {{ __('Footer Style') }} 4: Minimal --}}
<footer class="bg-white border-top text-center py-4">
    <div class="container">
        <p class="small text-muted mb-2">{{ $generalsetting->copyright ?? '© 2026' }}</p>
        <div class="d-flex justify-content-center gap-3 small flex-wrap">
            <a href="{{ route('home') }}" class="text-muted text-decoration-none">{{ __('Home') }}</a>
            <a href="{{ route('shop') }}" class="text-muted text-decoration-none">{{ __('Shop') }}</a>
            <a href="{{ route('brands') }}" class="text-muted text-decoration-none">{{ __('Brands') }}</a>
            <a href="{{ route('blog.details') }}" class="text-muted text-decoration-none">{{ __('Blog') }}</a>
            <a href="{{ route('contact') }}" class="text-muted text-decoration-none">{{ __('Contact') }}</a>
            <a href="#" class="text-muted text-decoration-none">{{ __('Privacy') }}</a>
            <a href="#" class="text-muted text-decoration-none">{{ __('Terms') }}</a>
        </div>
    </div>
</footer>
