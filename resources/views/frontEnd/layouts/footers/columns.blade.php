{{-- Footer Style 5: Columns with App Store buttons --}}
<footer class="border-top" style="background:#f0f4f8;">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <img src="{{ asset($generalsetting->dark_logo ?? 'public/assets/images/CurlBazar.png') }}" alt="Logo" style="max-height:35px;" class="mb-2">
                <p class="small text-secondary">{{ $generalsetting->footer_about_text ?? '' }}</p>
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ $generalsetting->google_play_link ?? '#' }}" target="_blank"><img src="{{ asset('public/assets/images/play.svg') }}" alt="Google Play" style="height:35px;"></a>
                    <a href="{{ $generalsetting->app_store_link ?? '#' }}" target="_blank"><img src="{{ asset('public/assets/images/app.png') }}" alt="App Store" style="height:35px;"></a>
                </div>
            </div>
            @foreach([['Shop','shop','hotdeals','flashsales'],['Help','contact','faq','shipping'],['Account','customer.dashboard','orders','customer.dashboard'],['Connect','facebook','instagram','youtube']] as $col)
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="fw-bold small mb-3">{{ $col[0] }}</h6>
                <ul class="list-unstyled small text-secondary">
                    @foreach(array_slice($col,1) as $item)
                    <li class="mb-1"><a href="{{ route($item) }}" class="text-secondary text-decoration-none">{{ ucfirst($item) }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold small mb-3">Contact</h6>
                <ul class="list-unstyled small text-secondary">
                    <li class="mb-1"><i class="fa-solid fa-phone me-2"></i>{{ $contact->hotline ?? 'N/A' }}</li>
                    <li class="mb-1"><i class="fa-solid fa-envelope me-2"></i>{{ $contact->email ?? 'N/A' }}</li>
                    <li class="mb-1"><i class="fa-solid fa-location-dot me-2"></i>{{ $contact->address ?? '' }}</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center small text-muted">{{ $generalsetting->copyright ?? '© 2026' }}</div>
    </div>
</footer>
