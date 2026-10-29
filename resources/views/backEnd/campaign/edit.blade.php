@extends('backEnd.layouts.master')
@section('title','Landing Page Edit')

@section('css')
@include('backEnd.campaign._styles')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0"><i class="fa fa-pencil-square-o mr-1"></i> Edit Landing Page</h4>
                <div>
                    <a href="{{ route('campaign.create') }}" class="btn btn-outline-primary rounded-pill btn-sm mr-1"><i class="fa fa-plus mr-1"></i> Create</a>
                    <a href="{{ route('campaign.index') }}" class="btn btn-primary rounded-pill btn-sm"><i class="fa fa-list mr-1"></i> Manage</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @include('backEnd.campaign._form', ['isEdit' => true])
    </div>
</div>

{{-- Reusable Media Gallery picker — "choose image from media library" --}}
@include('backEnd.media._picker')
@endsection

@section('script')
@include('backEnd.campaign._scripts')
@endsection
