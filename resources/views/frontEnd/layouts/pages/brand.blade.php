@extends('frontEnd.layouts.master')
@section('title', $brand->name)

@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}">
@endpush

@section('content')
<section class="product-section">
    <div class="container">

        {{-- 🔹 Breadcrumb + Sorting --}}
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">{{ __('Home') }}</a>
                        <span>/</span>
                        <strong>{{ $brand->name }}</strong>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>
                                    Showing {{ $products->firstItem() }}-{{ $products->lastItem() }}
                                    of {{ $products->total() }} Results
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="page-sort">
                                <form class="sort-form">
                                    <select name="sort" class="form-control form-select sort">
                                        <option value="1" @selected(request('sort')==1)>Product: Latest</option>
                                        <option value="2" @selected(request('sort')==2)>Product: Oldest</option>
                                        <option value="3" @selected(request('sort')==3)>Price: High To Low</option>
                                        <option value="4" @selected(request('sort')==4)>Price: Low To High</option>
                                        <option value="5" @selected(request('sort')==5)>Name: A-Z</option>
                                        <option value="6" @selected(request('sort')==6)>Name: Z-A</option>
                                    </select>

                                    <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                                    <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔹 Product Grid --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="category-product main_product_inner pt-2">

                    @foreach($products as $key => $value)
                    @include('frontEnd.layouts.sections.product-card', ['product' => $value, 'classes' => 'wow zoomIn', 'attrs' => 'data-wow-duration="1.5s" data-wow-delay="0.'.$key.'s"'])
                    @endforeach

                </div>
            </div>
        </div>

        {{-- 🔹 Pagination --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    {{ $products->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@push('script')
<script>
    $('.sort').change(function () {
        $('.sort-form').submit();
    });
</script>
@endpush
