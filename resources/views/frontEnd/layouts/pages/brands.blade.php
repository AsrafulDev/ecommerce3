@extends('frontEnd.layouts.master')
@section('title', '{{ __('All Brands') }}')

@push('css')
<style>
    .brands-page-header {
        background: #f8f9fa;
        padding: 30px 0;
        margin-bottom: 30px;
        text-align: center;
    }
    .brands-page-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #333;
    }
    .brand-card {
        display: block;
        text-align: center;
        padding: 20px 10px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        background: #fff;
        text-decoration: none;
        color: #333;
    }
    .brand-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        border-color: #ddd;
    }
    .brand-card .brand-img-wrapper {
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    .brand-card .brand-img-wrapper img {
        max-height: 80px;
        max-width: 100%;
        object-fit: contain;
    }
    .brand-card .brand-title {
        font-size: {{ __('14px') }};
        font-weight: 600;
        color: #555;
        margin: 0;
    }
    .pagination-wrapper {
        margin-top: 20px;
    }
</style>
@endpush

@section('content')
{{-- Page Header --}}
<div class="brands-page-header">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="category-breadcrumb d-flex align-items-center justify-content-center mb-2">
                    <a href="{{ route('home') }}">{{ __('Home') }}</a>
                    <span class="mx-2">/</span>
                    <strong>{{ __('All Brands') }}</strong>
                </div>
                <h2>{{ __('Our Brands') }}</h2>
                <p class="text-muted mt-2 mb-0">{{ __('Discover products from your favorite brands') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Brands Grid --}}
<section class="brands-section">
    <div class="container">
        <div class="row">
            @forelse($brands as $brand)
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="{{ route('brand.products', $brand->slug) }}" class="brand-card">
                        <div class="brand-img-wrapper">
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
                        <h3 class="brand-title">{{ $brand->name }}</h3>
                    </a>
                </div>
            @empty
                <div class="col-sm-12 text-center py-5">
                    <h4>{{ __('No brands found') }}</h4>
                    <p class="text-muted">{{ __('Please check back later.') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($brands->hasPages())
            <div class="row">
                <div class="col-sm-12">
                    <div class="pagination-wrapper d-flex justify-content-center">
                        {{ $brands->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
