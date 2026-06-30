{{-- Hero Slider (Inner Container) --}}
<section class="hero-inner-section">
    <div class="container">
        <div class="hero-inner-slider owl-carousel">
            @foreach ($sliders as $key => $value)
            <div class="hero-inner-slide">
                <a href="{{ $value->link ?? '#' }}" class="hero-inner-link">
                    <img src="{{ asset($value->image) }}" 
                         class="hero-inner-img" 
                         alt="Hero {{ $key + 1 }}"
                         loading="{{ $key === 0 ? 'eager' : 'lazy' }}">
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

@push('css')
<style>
    .hero-inner-section {
        padding: 0;
    }
    .hero-inner-slider {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .hero-inner-slide {
        position: relative;
        overflow: hidden;
    }
    .hero-inner-link {
        display: block;
    }
    .hero-inner-img {
        width: 100%;
        height: auto;
        max-height: 420px;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }
    .hero-inner-slide:hover .hero-inner-img {
        transform: scale(1.02);
    }

    /* Owl nav */
    .hero-inner-section .owl-nav button {
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
    .hero-inner-section:hover .owl-nav button {
        opacity: 1;
    }
    .hero-inner-section .owl-prev { left: 12px; }
    .hero-inner-section .owl-next { right: 12px; }
    .hero-inner-section .owl-nav button:hover {
        background: #fff !important;
        box-shadow: 0 4px {{ __('14px') }} rgba(0,0,0,0.18);
    }

    @media (max-width: 767px) {
        .hero-inner-img { max-height: 250px; }
        .hero-inner-slider { border-radius: 0; box-shadow: none; }
    }
    @media (min-width: 1400px) {
        .hero-inner-img { max-height: 500px; }
    }
</style>
@endpush
