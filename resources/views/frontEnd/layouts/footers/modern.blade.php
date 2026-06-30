{{-- {{ __('Footer Style') }} 2: Modern --}}
<footer style="background:#f8f9fa;border-top:3px solid var(--primary-color);">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold mb-3">{{ $generalsetting->name ?? 'Ecommerce' }}</h5>
                <p class="text-muted small">{{ $generalsetting->footer_about_text ?? 'Your trusted online shopping destination.' }}</p>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-dark btn-sm rounded-pill px-3"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-dark btn-sm rounded-pill px-3"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-dark btn-sm rounded-pill px-3"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-6 col-md-3"><h6 class="fw-bold small mb-2">{{ __('Shop') }}</h6><ul class="list-unstyled small text-muted"><li class="mb-1"><a href="{{ route('shop') }}" class="text-decoration-none text-muted">{{ __('All {{ __('Product') }}s') }}</a></li><li class="mb-1"><a href="{{ route('hotdeals') }}" class="text-decoration-none text-muted">{{ __('{{ __('Hot Deal') }}s') }}</a></li><li class="mb-1"><a href="#" class="text-decoration-none text-muted">{{ __('{{ __('New') }} Arrivals') }}</a></li></ul></div>
                    <div class="col-6 col-md-3"><h6 class="fw-bold small mb-2">{{ __('Help') }}</h6><ul class="list-unstyled small text-muted"><li class="mb-1"><a href="#" class="text-decoration-none text-muted">{{ __('FAQ') }}</a></li><li class="mb-1"><a href="#" class="text-decoration-none text-muted">{{ __('Shipping') }}</a></li><li class="mb-1"><a href="{{ route('contact') }}" class="text-decoration-none text-muted">{{ __('Contact') }}</a></li></ul></div>
                    <div class="col-6 col-md-3"><h6 class="fw-bold small mb-2">{{ __('Account') }}</h6><ul class="list-unstyled small text-muted"><li class="mb-1"><a href="{{ route('customer.account') }}" class="text-decoration-none text-muted">{{ __('My Account') }}</a></li><li class="mb-1"><a href="#" class="text-decoration-none text-muted">{{ __('Orders') }}</a></li><li class="mb-1"><a href="#" class="text-decoration-none text-muted">{{ __('Wishlist') }}</a></li></ul></div>
                    <div class="col-6 col-md-3"><h6 class="fw-bold small mb-2">{{ __('Contact') }}</h6><ul class="list-unstyled small text-muted"><li class="mb-1"><i class="fa-solid fa-{{ __('phone') }} me-1"></i>{{ $contact->hotline ?? '{{ __('N/A') }}' }}</li><li class="mb-1"><i class="fa-solid fa-envelope me-1"></i>{{ $contact->email ?? '{{ __('N/A') }}' }}</li></ul></div>
                </div>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center small text-muted">
            <span>{{ $generalsetting->copyright ?? '© 2026' }}</span>
            <span>{{ __('Powered by Ecommerce3') }}</span>
        </div>
    </div>
</footer>
