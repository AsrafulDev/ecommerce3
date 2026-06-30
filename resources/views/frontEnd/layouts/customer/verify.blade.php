@extends('frontEnd.layouts.master')
@section('title','{{ __('{{ __('Customer') }} Verify') }}')
@section('content')
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-5">
                <div class="form-content">
                    <p class="auth-title">{{ __('{{ __('Customer') }} Verify') }}</p>
                    <form action="{{route('customer.account.verify')}}" method={{ __('"{{ __('POST') }}"') }}  data-parsley-validate="">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="otp">{{ __('OTP') }}</label>
                            <input type="{{ __('number') }}" id="otp" class="form-control @error('otp') is-invalid @enderror" name="otp" value="{{ old('otp') }}" placeholder="{{ __('Enter {{ __('OTP') }}') }}" required>
                            @error('{{ __('phone') }}')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <!-- col-end -->
                        <div class="form-group mb-3">
                            <button class="{{ __('submit') }}-btn">{{ __('submit') }}</button>
                        </div>
                     <!-- col-end -->
                     </form>
                     <div class="resend_otp">
                        <form action="{{route('customer.resendotp')}}" method={{ __('"{{ __('POST') }}"') }}>
                            @csrf
                            <button><i data-feather="rotate-cw"></i> {{ __('Resend {{ __('OTP') }}') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('script')
<script src="{{asset('public/frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('public/frontEnd/')}}/js/form-validation.init.js"></script>
@endpush