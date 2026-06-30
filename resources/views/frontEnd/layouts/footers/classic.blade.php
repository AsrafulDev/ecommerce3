{{-- {{ __('Footer Style') }} 1: {{ __('Classic') }} (Default) --}}
<footer class="footer-classic text-white" style="background:#1a1a1a;">
    <div class="container pt-5 pb-3">
        <div class="row g-4">
            {{-- About --}}
            <div class="col-lg-3 col-md-6">
                <img src="{{ asset($generalsetting->dark_logo ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:40px;{{ __('filter') }}:brightness(10);" class="mb-3">
                <p class="small text-white-50">{{ $generalsetting->footer_about_text ?? 'Your trusted online store.' }}</p>
                <a href="tel:{{ $contact->hotline ?? '' }}" class="text-white-50 small">{{ $contact->hotline ?? '' }}</a>
                <div class="mt-2 d-flex gap-2">
                    @foreach(($socials ?? collect())->take(4) as $s)
                        <a href="{{ $s->url ?? '#' }}" class="btn btn-sm btn-outline-light rounded-circle" style="width:32px;height:32px;padding:0;line-height:30px;" target="_blank">
                            <i class="{{ $s->icon ?? 'fa-brands fa-facebook' }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            {{-- Quick {{ __('{{ __('Link') }}s') }} --}}
            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase mb-3">{{ __('Quick {{ __('{{ __('Link') }}s') }}') }}</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">{{ __('Home') }}</a></li>
                    <li class="mb-1"><a href="{{ route('shop') }}" class="text-white-50 text-decoration-none">{{ __('Shop') }}</a></li>
                    <li class="mb-1"><a href="{{ route('hotdeals') }}" class="text-white-50 text-decoration-none">{{ __('{{ __('Hot Deal') }}s') }}</a></li>
                    <li class="mb-1"><a href="{{ route('brands') }}" class="text-white-50 text-decoration-none">{{ __('Brands') }}</a></li>
                    <li class="mb-1"><a href="{{ route('blog.details') }}" class="text-white-50 text-decoration-none">{{ __('Blog') }}</a></li>
                </ul>
            </div>
            {{-- {{ __('Customer') }} Service --}}
            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase mb-3">{{ __('Support') }}</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="{{ route('contact') }}" class="text-white-50 text-decoration-none">{{ __('{{ __('Contact') }} Us') }}</a></li>
                    <li class="mb-1"><a href="#" class="text-white-50 text-decoration-none">{{ __('FAQ') }}</a></li>
                    <li class="mb-1"><a href="#" class="text-white-50 text-decoration-none">{{ __('Shipping') }}</a></li>
                    <li class="mb-1"><a href="#" class="text-white-50 text-decoration-none">{{ __('Returns') }}</a></li>
                </ul>
            </div>
            {{-- {{ __('New') }}sletter --}}
            <div class="col-lg-5 col-md-6">
                <h6 class="text-uppercase mb-3">{{ __('{{ __('New') }}sletter') }}</h6>
                <p class="small text-white-50">{{ __('Subscribe for exclusive offers & updates.') }}</p>
                <form class="d-flex gap-2">
                    <input type="email" class="form-control form-control-sm bg-dark border-0 text-white" placeholder="{{ __('Your email') }}">
                    <button class="btn btn-sm text-white" style="background:var(--primary-color);">{{ __('Subscribe') }}</button>
                </form>
            </div>
        </div>
        <hr class="border-secondary mt-4">
        <div class="text-center small text-white-50 py-2">
            {{ $generalsetting->copyright ?? '© 2026 All rights reserved.' }}
        </div>
    </div>
</footer>
