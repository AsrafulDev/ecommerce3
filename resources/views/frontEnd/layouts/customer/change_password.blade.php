@extends('frontEnd.layouts.master')
@section('title','{{ __('Change') }} Password')

@section('content')
<section class="customer-section">
    <div class="container">
        <div class="row">

            {{-- Sidebar --}}
            <div class="col-sm-3">
                <div class="customer-sidebar">
                    @include('frontEnd.layouts.customer.sidebar')
                </div>
            </div>

            {{-- Main Content --}}
            <div class="col-sm-9">
                <div class="customer-content checkout-shipping account-card">

                    <h5 class="account-title">{{ __('{{ __('Change') }} Password') }}</h5>
                    <div class="account-divider"></div>

                    <form action="{{ route('customer.password_update') }}"
                          method={{ __('"{{ __('POST') }}"') }}
                          class="row"
                          data-parsley-validate>
                        @csrf

                        {{-- Old Password --}}
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="old_password">{{ __('Old {{ __('Password *') }}') }}</label>
                                <input type="password"
                                       id="old_password"
                                       name="old_password"
                                       class="form-control @error('old_password') is-invalid @enderror"
                                       placeholder="{{ __('Enter old password') }}"
                                       required>
                                @error('old_password')
                                    <span class="invalid-feedback">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- {{ __('New') }} Password --}}
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="new_password">{{ __('{{ __('New') }} {{ __('Password *') }}') }}</label>
                                <input type="password"
                                       id="new_password"
                                       name="new_password"
                                       class="form-control @error('new_password') is-invalid @enderror"
                                       placeholder="{{ __('Enter new password') }}"
                                       required>
                                @error('new_password')
                                    <span class="invalid-feedback">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-sm-12">
                            <div class="form-group mb-4">
                                <label for="confirm_password">{{ __('Confirm {{ __('Password *') }}') }}</label>
                                <input type="password"
                                       id="confirm_password"
                                       name="confirm_password"
                                       class="form-control @error('confirm_password') is-invalid @enderror"
                                       placeholder="{{ __('Confirm new password') }}"
                                       required>
                                @error('confirm_password')
                                    <span class="invalid-feedback">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="col-sm-12 text-center">
                            <button type="{{ __('submit') }}" class="{{ __('submit') }}-btn">
                                Update Password
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('script')
<script src="{{ asset('public/frontEnd/js/parsley.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/form-validation.init.js') }}"></script>
@endpush
