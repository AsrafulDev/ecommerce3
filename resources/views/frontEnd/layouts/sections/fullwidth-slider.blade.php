{{-- Full-Width Hero Slider --}}
<section class="fullwidth-slider-section">
    <div class="fullwidth-slider owl-carousel">
        @foreach ($sliders as $key => $value)
        <div class="fw-slide">
            <a href="{{ $value->link ?? '#' }}" class="fw-slide-link">
                <img src="{{ asset($value->image) }}" 
                     class="fw-slide-img" 
                     alt="Fullwidth Slider {{ $key + 1 }}"
                     loading="{{ $key === 0 ? 'eager' : 'lazy' }}">
            </a>
        </div>
        @endforeach
    </div>
</section>

@push('css')
<style>
    .fullwidth-slider-section {
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .fullwidth-slider {
        width: 100%;
    }
    .fw-slide {
        position: relative;
        overflow: hidden;
    }
    .fw-slide-link {
        display: block;
        width: 100%;
    }
    .fw-slide-img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
        display: block;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .fw-slide:hover .fw-slide-img {
        transform: scale(1.03);
    }

    /* Owl nav — centered, semi-transparent circles */
    .fullwidth-slider-section .owl-nav button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 48px;
        height: 48px;
        border-radius: 50% !important;
        background: rgba(255,255,255,0.8) !important;
        color: #333 !important;
        font-size: 22px !important;
        line-height: 44px !important;
        box-shadow: 0 3px 12px rgba(0,0,0,0.15);
        transition: all 0.35s ease;
        opacity: 0;
    }
    .fullwidth-slider-section:hover .owl-nav button {
        opacity: 1;
    }
    .fullwidth-slider-section .owl-prev { left: 20px; }
    .fullwidth-slider-section .owl-next { right: 20px; }
    .fullwidth-slider-section .owl-nav button:hover {
        background: #fff !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        transform: translateY(-50%) scale(1.05);
    }

    /* Owl dots — centered at bottom */
    .fullwidth-slider-section .owl-dots {
        position: absolute;
        bottom: 18px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
    }
    .fullwidth-slider-section .owl-dot span {
        width: 10px;
        height: 10px;
        background: rgba(255,255,255,0.5) !important;
        border-radius: 50%;
        transition: all 0.3s;
    }
    .fullwidth-slider-section .owl-dot.active span,
    .fullwidth-slider-section .owl-dot:hover span {
        background: #fff !important;
        width: 28px;
        border-radius: 5px;
    }

    @media (max-width: 767px) {
        .fw-slide-img { max-height: 250px; }
        .fullwidth-slider-section .owl-nav button {
            width: 36px; height: 36px; font-size: 16px !important;
        }
        .fullwidth-slider-section .owl-prev { left: 8px; }
        .fullwidth-slider-section .owl-next { right: 8px; }
        .fullwidth-slider-section .owl-dots { bottom: 10px; }
    }
    @media (min-width: 1400px) {
        .fw-slide-img { max-height: 600px; }
    }
</style>
@endpush
