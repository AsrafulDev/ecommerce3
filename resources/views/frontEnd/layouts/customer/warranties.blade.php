@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('My Warranties');
    $headerTitle = __('My Warranties');
    $headerSubtitle = __('Track your warranty coverage and file claims');
@endphp

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    @include('frontEnd.layouts.customer.my-warranties')
</div>
@endsection
