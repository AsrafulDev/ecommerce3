@extends('backEnd.layouts.master')
@section('title', 'Manual Duplicate Order {{ __('Check') }}')

@section('content')

<style>
    /* Success Circle */
    .success-circle {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 12px solid #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e9f7ef;
        margin: 20px auto;
    }

    /* {{ __('Status') }} Box */
    .status-box {
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        margin-top: 15px;
    }
    .status-green { border: 2px solid green; color: green; background: #eaffea; }
    .status-blue { border: 2px solid #0d6efd; color: #0d6efd; background: #eef6ff; }
    .status-orange { border: 2px solid orange; color: orange; background: #fff8e1; }
    .status-red { border: 2px solid red; color: red; background: #ffeeee; }
</style>

<div class="container-fluid py-4">
    <div class="card shadow-sm p-4">

        <h4 class="text-center fw-bold mb-4">
            {{ __('bn_edb98e65') }} চেক করতে {{ __('{{ __('Mobile') }} Number') }}টি দিয়ে {{ __('Search') }}
        </h4>

        {{-- Search Box --}}
        <form action="{{ route('manualDuplicateOrder.check') }}" method={{ __('"{{ __('POST') }}"') }} class="text-center mb-4">
            @csrf
            <div class="input-group justify-content-center" style="max-width:400px; margin:auto;">
                <input type="text" name="mobile" class="form-control text-center"
                    value="{{ $mobile ?? '' }}" placeholder="{{ __('017XXXXXXXX') }}" required>
                <button class="btn btn-success px-4">{{ __('Search') }}</button>
            </div>
        </form>

        {{-- API Error {{ __('Message') }} --}}
        @if(session('error'))
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-danger text-center">
                        <strong>{{ session('error') }}</strong>
                    </div>
                </div>
            </div>
        @endif

        {{-- Results --}}
        @if(isset($data) && !empty($data))
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm p-4">

                    <h5 class="fw-bold bg-primary text-white py-2 rounded text-center mb-4">
                        {{ __('bn_edb98e65') }} তথ্য
                    </h5>

                    <h3 class="text-center fw-bold text-primary mt-3">
                        # {{ $mobile }}
                    </h3>

                    @php
                        $isDuplicate = $data['is_duplicate'] ?? false;
                        $duplicateCount = $data['duplicate_count'] ?? 0;
                        $duplicate{{ __('Rate') }} = $data['duplicate_rate'] ?? 0;
                        $lastDuplicate{{ __('Date') }} = $data['last_duplicate_date'] ?? null;
                    @endphp

                    {{-- Circle --}}
                    <div class="success-circle" style="border-color: {{ $isDuplicate ? '#dc3545' : '#28a745' }}">
                        <div class="text-center">
                            <span class="fw-bold fs-2" style="color: {{ $isDuplicate ? '#dc3545' : '#28a745' }}">
                                {{ $duplicateCount }}
                            </span>
                            <br>
                            <small class="text-muted" style="font-size: 12px;">{{ __('bn_edb98e65') }}</small>
                        </div>
                    </div>

                    {{-- {{ __('Status') }} {{ __('Message') }} --}}
                    @if($isDuplicate)
                        <div class="status-box status-red">
                            <h5 class="fw-bold">{{ __('bn_94793b14') }}</h5>
                            <p class="mb-0">এই {{ __('{{ __('Mobile') }} Number') }} দিয়ে {{ $duplicateCount }} টি {{ __('bn_edb98e65') }} পাওয়া গেছে।</p>
                            @if($lastDuplicate{{ __('Date') }})
                                <p class="mb-0 mt-2"><small>সর্বশেষ {{ __('bn_edb98e65') }}: {{ $lastDuplicate{{ __('Date') }} }}</small></p>
                            @endif
                        </div>
                    @else
                        <div class="status-box status-green">
                            <h5 class="fw-bold">{{ __('bn_d85d23fa') }}</h5>
                            <p class="mb-0">{{ __('bn_627d0ed0') }}</p>
                        </div>
                    @endif

                    {{-- Additional Info --}}
                    @if(isset($data['details']))
                    <div class="mt-4">
                        <h6 class="fw-bold">{{ __('bn_f0832243') }}:</h6>
                        <pre class="bg-light p-3 rounded">{{ json_encode($data['details'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection
