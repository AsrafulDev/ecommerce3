{{-- Brands Section --}}
@if(isset($brands) && $brands->count() > 0)
@push('css')
<style>
    .brand-section {
        padding: 60px 0;
        background: #fafbfc;
    }
    .brand-section .section-header {
        text-align: center;
        margin-bottom: 35px;
    }
    .brand-section .section-title {
        font-size: 26px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 6px;
        position: relative;
        display: inline-block;
        padding-bottom: 12px;
    }
    .brand-section .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: var(--primary-color, #0d6efd);
        border-radius: 2px;
    }
    .brand-section .section-subtitle {
        font-size: 14px;
        color: #777;
        margin-top: 8px;
    }
    .brand-section .brand-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #eef0f2;
        border-radius: 12px;
        padding: 18px 12px 14px;
        height: 100%;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .brand-section .brand-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--primary-color, #0d6efd);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    .brand-section .brand-card:hover {
        border-color: #d0d5dd;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transform: translateY(-4px);
    }
    .brand-section .brand-card:hover::before {
        transform: scaleX(1);
    }
    .brand-section .brand-img-wrap {
        width: 90px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    .brand-section .brand-img-wrap img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
        filter: grayscale(20%);
        opacity: 0.85;
    }
    .brand-section .brand-card:hover .brand-img-wrap img {
        filter: grayscale(0%);
        opacity: 1;
        transform: scale(1.08);
    }
    .brand-section .brand-name {
        font-size: 13px;
        font-weight: 600;
        color: #444;
        text-align: center;
        line-height: 1.3;
        transition: color 0.3s ease;
    }
    .brand-section .brand-card:hover .brand-name {
        color: var(--primary-color, #0d6efd);
    }
    .brand-section .brand-footer {
        text-align: center;
        margin-top: 30px;
    }
    .brand-section .view-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 28px;
        background: var(--primary-color, #0d6efd);
        color: #fff;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .brand-section .view-all-btn:hover {
        background: #0b5ed7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13,110,253,0.3);
        color: #fff;
        text-decoration: none;
    }
    .brand-section .view-all-btn svg {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
    }
    .brand-section .view-all-btn:hover svg {
        transform: translateX(3px);
    }

    @media (max-width: 767px) {
        .brand-section { padding: 40px 0; }
        .brand-section .section-title { font-size: 22px; }
        .brand-section .brand-card { padding: 14px 8px 10px; border-radius: 10px; }
        .brand-section .brand-img-wrap { width: 70px; height: 55px; }
        .brand-section .brand-name { font-size: 12px; }
    }
    @media (max-width: 575px) {
        .brand-section .brand-img-wrap { width: 60px; height: 48px; margin-bottom: 8px; }
        .brand-section .brand-name { font-size: 11px; }
    }
</style>
@endpush

<section class="brand-section">
    <div class="container">
        {{-- Section Header --}}
        <div class="section-header">
            <h3 class="section-title">Top Brands</h3>
            <p class="section-subtitle">Shop from your favorite trusted brands</p>
        </div>

        {{-- Brands Grid --}}
        <div class="row g-3 justify-content-center">
            @foreach($brands as $brand)
                <div class="col-lg-2 col-md-3 col-sm-4 col-4">
                    <a href="{{ route('brand.products', $brand->slug) }}" class="brand-card">
                        <div class="brand-img-wrap">
                            @if($brand->image)
                                <img src="{{ asset($brand->image) }}" 
                                     alt="{{ $brand->name }}" 
                                     loading="lazy">
                            @else
                                <img src="{{ asset('public/uploads/images/_placeholder.jpg') }}" 
                                     alt="{{ $brand->name }}" 
                                     loading="lazy">
                            @endif
                        </div>
                        <span class="brand-name">{{ $brand->name }}</span>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- View All Link --}}
        <div class="brand-footer">
            <a href="{{ route('brands') }}" class="view-all-btn">
                View All Brands
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>
    </div>
</section>
@endif
