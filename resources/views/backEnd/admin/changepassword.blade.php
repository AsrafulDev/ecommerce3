@extends('backEnd.layouts.master')
@section('title','{{ __('Change') }} Password')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{url('admin/dashboard')}}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{route('users.index')}}">{{ __('{{ __('{{ __('Use') }}r') }}s') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('{{ __('Change') }} Password') }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ __('{{ __('Change') }} Password') }}</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{route('new_password')}}" method={{ __('"{{ __('POST') }}"') }} class=row data-parsley-validate=""  enctype="multipart/form-data">
                    @csrf
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="old_password" class="form-label">{{ __('Old {{ __('Password *') }}') }}</label>
                            <input type="password" class="form-control @error('old_password') is-invalid @enderror" name="old_password" value="{{ old('old_password') }}" id="old_password" required="">
                            @error('old_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="new_password" class="form-label">{{ __('{{ __('New') }} {{ __('Password *') }}') }}</label>
                            <input type="password" class="form-control @error('new_password') is-invalid @enderror" name="new_password" value="{{ old('new_password') }}" id="new_password" required="">
                            @error('new_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="confirm_password" class="form-label">{{ __('Confirm {{ __('Password *') }}') }}</label>
                            <input type="password" class="form-control @error('confirm_password') is-invalid @enderror" name="confirm_password" value="{{ old('confirm_password') }}"  id="confirm_password" required="">
                            @error('confirm_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div>
                        <input type="{{ __('submit') }}" class="btn btn-success" value="{{ __('Change') }} Password">
                    </div>

                </form>

            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
   </div>
</div>
@endsection


@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
@endsection