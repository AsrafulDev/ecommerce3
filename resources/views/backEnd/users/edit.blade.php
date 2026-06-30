@extends('backEnd.layouts.master')
@section('title','{{ __('{{ __('{{ __('{{ __('Use') }}r') }}s') }} Edit') }}')
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
                    <a href="{{route('users.index')}}" class="btn btn-primary rounded-pill">{{ __('Manage') }}</a>
                </div>
                <h4 class="page-title">{{ __('{{ __('{{ __('{{ __('Use') }}r') }}s') }} Edit') }}</h4>
            </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{route('users.update')}}" method={{ __('"{{ __('POST') }}"') }} class=row data-parsley-validate=""  enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">{{ __('{{ __('Name') }} *') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}" id="name" required="">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col-end -->
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">{{ __('{{ __('Email') }} *') }}</label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $edit_data->email}}"  id="email" required="">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col-end -->
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">{{ __('Password *') }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" value="" id="password" >
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="confirm-password" class="form-label">{{ __('Confirm {{ __('Password *') }}') }}</label>
                            <input type="password" class="form-control @error('confirm-password') is-invalid @enderror" name="confirm-password" value=""  id="confirm-password" >
                            @error('confirm-password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
<div class="col-sm-6">
    <div class="form-group mb-3">
        <label for="roles" class="form-label">{{ __('Role *') }}</label>

        @php
            // এই ইউজারের আগে থেকে থাকা রোলগুলোর নাম নিয়ে নিলাম
            $userRole{{ __('Name') }}s = $edit_data->roles->pluck('name')->toArray();
        @endphp

        {{-- যদি লগইন করা ইউজার নিজেই নিজের প্রো{{ __('File') }} এডিট করে --}}
        @if(auth()->id() == $edit_data->{{ __('id)') }}

            {{-- রোল দেখাবে, কিন্তু চেঞ্জ করা যাবে না (disabled) --}}
            <select class="form-control select2-multiple" 
                    data-toggle="select2"
                    multiple="multiple" 
                    data-placeholder="{{ __('Choose ...') }}" 
                    disabled>
                <optgroup label="{{ __('Select Role') }}">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}"
                            {{ in_array($role->name, $userRole{{ __('Name') }}s) ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </optgroup>
            </select>

            {{-- Hidden input দিয়ে পুরোনো রোলগুলো ফর্মে পাঠিয়ে দিচ্ছি,
                 যেন update করার সময় রোল নষ্ট না {{ __('bn_290a7f61') }} --}}
            @foreach($userRole{{ __('Name') }}s as $r{{ __('Name') }})
                <input type="hidden" name="roles[]" value="{{ $r{{ __('Name') }} }}">
            @endforeach

            <small class="text-danger d-block mt-1">
                আপনি নিজের একাউন্টের Role পরিবর্তন করতে পারবেন না।
            </small>

        @else
            {{-- অন্য ইউজার হলে, নরমাল Editable select --}}
            <select class="form-control select2-multiple" 
                    name="roles[]" 
                    data-toggle="select2"
                    multiple="multiple" 
                    data-placeholder="{{ __('Choose ...') }}" 
                    required>
                <optgroup label="{{ __('Select Role') }}">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}"
                            {{ in_array($role->name, $userRole{{ __('Name') }}s) ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </optgroup>
            </select>

            @error('roles')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        @endif

    </div>
</div>

                    <!-- col end -->
                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="image" class="form-label">{{ __('Image *') }}</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" value="{{ $edit_data->image }}"  id="image" >
                            <img src="{{asset($edit_data->image)}}" alt="">
                            @error('image')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="status" class="d-block">{{ __('Status') }}</label>
                            <label class="switch">
                              <input type="checkbox" value="1" name="status" @if($edit_data->status==1)checked @endif>
                              <span class="slider round"></span>
                            </label>
                            @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
                    <div>
                        <input type="{{ __('submit') }}" class="btn btn-success" value="Submit">
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
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>
@endsection