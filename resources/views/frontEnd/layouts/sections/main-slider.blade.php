{{-- Left Category Hero Slider --}}
<section class="hero-section">
    <div class="container">
        <div class="row g-0 hero-row">
            {{-- Sidebar Categories (Left) --}}
            <div class="col-lg-3 col-md-4 hidetosm">
                @include('frontEnd.layouts.sections.sidebar-categories')
            </div>

            {{-- Hero Slider (Right) --}}
            <div class="col-lg-9 col-md-8">
                <div class="hero-slider owl-carousel">
                    @foreach ($sliders as $key => $value)
                    <div class="hero-slide">
                        <a href="{{ $value->link ?? '#' }}" class="hero-slide-link">
                            <img src="{{ asset($value->image) }}" 
                                 class="hero-slide-img" 
                                 alt="Hero Slider {{ $key + 1 }}"
                                 loading="{{ $key === 0 ? 'eager' : 'lazy' }}">
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@push('css')
<style>
    .hero-section {
        padding: 10px 0 0;
    }
    .hero-row {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        background: #fff;
    }
    .hero-row .hidetosm {
        border-right: 1px solid #f0f0f0;
    }
    .hero-slider {
        height: 100%;
    }
    .hero-slide {
        position: relative;
        overflow: hidden;
    }
    .hero-slide-link {
        display: block;
    }
    .hero-slide-img {
        width: 100%;
        height: auto;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }
    .hero-slide:hover .hero-slide-img {
        transform: scale(1.02);
    }

    /* Owl nav buttons */
    .hero-section .owl-nav button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        border-radius: 50% !important;
        background: rgba(255,255,255,0.85) !important;
        color: #333 !important;
        font-size: 18px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        transition: all 0.3s;
        opacity: 0;
    }
    .hero-section:hover .owl-nav button {
        opacity: 1;
    }
    .hero-section .owl-prev { left: 12px; }
    .hero-section .owl-next { right: 12px; }
    .hero-section .owl-nav button:hover {
        background: #fff !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }

    @media (max-width: 991px) {
        .hero-section .hidetosm { display: none !important; }
        .hero-row { border-radius: 0; box-shadow: none; }
    }
</style>
@endpush
