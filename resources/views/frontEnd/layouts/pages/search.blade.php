@extends('frontEnd.layouts.master') 
@section('title',$keyword)
@push('dataLayer')
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ event: 'search', search_term: '{{ addslashes($keyword ?? '') }}' });
if (typeof fbq === 'function') fbq('track', 'Search', { search_string: '{{ addslashes($keyword ?? '') }}' });
if (typeof ttq !== 'undefined') ttq.track('Search', { query: '{{ addslashes($keyword ?? '') }}' });
@endpush
@push('css')
<link rel="stylesheet" href="{{asset('public/frontEnd/css/jquery-ui.css')}}" />
@endpush 

@section('content')
<section class="product-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">{{ __('Home') }}</a>
                        <span>/</span>
                        <strong>{{ $keyword }}</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>@if($products->total() > 0)Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of @endif{{ $products->total() }} Results</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mobile-filter-toggle">
                                <i class="fa fa-list-ul"></i><span>filter</span>
                            </div>
                            <div class="page-sort">
                                <form action="" class="sort-form">
                                    <select name="sort" class="form-control form-select sort">
                                        <option value="1" @if(request()->get('sort')==1)selected @endif>Product: Latest</option>
                                        <option value="2" @if(request()->get('sort')==2)selected @endif>Product: Oldest</option>
                                        <option value="3" @if(request()->get('sort')==3)selected @endif>Price: High To Low</option>
                                        <option value="4" @if(request()->get('sort')==4)selected @endif>Price: Low To High</option>
                                        <option value="5" @if(request()->get('sort')==5)selected @endif>Name: A-Z</option>
                                        <option value="6" @if(request()->get('sort')==6)selected @endif>Name: Z-A</option>
                                    </select>
                                    <input type="hidden" name="keyword" value="{{ request()->get('keyword') ?: request()->get('q') }}" />
                                    <input type="hidden" name="min_price" value="{{ request()->get('min_price') }}" />
                                    <input type="hidden" name="max_price" value="{{ request()->get('max_price') }}" />
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <div class="category-product main_product_inner pt-2">
                    @foreach($products as $key=>$value)
                    @include('frontEnd.layouts.sections.product-card', ['product' => $value, 'classes' => 'wow zoomIn', 'attrs' => 'data-wow-duration="1.5s" data-wow-delay="0.'.$key.'s"'])
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    {{$products->links('pagination::bootstrap-4')}}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('script')
<script>
    $(".sort").change(function(){
       $('#loading').show();
       $(".sort-form").submit();
    })
</script>
@endpush
