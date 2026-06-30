@extends('frontEnd.layouts.master')
@section('title','{{ __('Order {{ __('Note') }}') }}')
@section('content')
<section class="customer-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <div class="customer-sidebar">
                    @include('frontEnd.layouts.customer.sidebar')
                </div>
            </div>
            <div class="col-sm-9">
                <div class="customer-content">
                    <h5 class="account-title">{{ __('Order {{ __('Note') }}') }} [{{ __('{{ __('Inv') }}oice') }} #{{$order->invoice_id}}]</h5>
                     <a href="{{route('customer.orders')}}"><strong><i class="fa-solid fa-arrow-left"></i> {{ __('Back To Order') }}</strong></a>
                    <div class="card mt-2">
                        <div class="card-body">
                            {!! $order->admin_note !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection