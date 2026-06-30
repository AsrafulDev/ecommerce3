@extends('backEnd.layouts.master')
@section('title', '{{ __('Manual {{ __('Fraud {{ __('Check') }}') }}') }}')

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

    /* {{ __('Courier') }} Logo */
    .courier-logo {
        width: 60px;
        height: 45px;
        object-fit: contain;
        margin-right: 8px;
        border-radius: 4px;
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

    /* Table Fix */
    table thead tr th {
        background: #2e8b57 !important;
        color: white !important;
    }

    /* Success {{ __('Rate') }} Round Badge */
    .rate-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 4px solid #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-weight: bold;
        color: #28a745;
        background: #e9ffe9;
        font-size: {{ __('14px') }};
    }
</style>

<div class="container-fluid py-4">
    <div class="card shadow-sm p-4">

        <h4 class="text-center fw-bold mb-4">
            আপনার যাচাই করতে চাওয়া {{ __('{{ __('Mobile') }} Number') }}টি দিয়ে {{ __('Search') }}
        </h4>

        {{-- Search Box --}}
        {{-- Route {{ __('Name') }} {{ __('Fixed') }}: admin.manual_fraud_check --}}
        <form action="{{ route('manualFraud.check') }}" method={{ __('"{{ __('POST') }}"') }} class="text-center mb-4">
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

            {{-- LEFT: SUMMARY SECTION --}}
            <div class="col-md-4">
                <div class="card shadow-sm p-4">

                    <h5 class="fw-bold bg-success text-white py-2 rounded text-center">
                        {{ __('bn_70ac0f2d') }} {{ __('bn_fbbc3031') }}তার {{ __('bn_f29420ce') }}
                    </h5>

                    <h3 class="text-center fw-bold text-success mt-3">
                        # {{ $mobile }}
                    </h3>

                    @php
                        $totalParcels = (int) ($data['{{ __('total') }}_parcels'] ?? 0);
                        $total{{ __('Delivered') }} = (int) ($data['{{ __('total') }}_delivered'] ?? 0);
                        $totalCancel = (int) ($data['{{ __('total') }}_cancel'] ?? 0);
                        $overall{{ __('Rate') }} = $totalParcels > 0 ? round(($total{{ __('Delivered') }} / $totalParcels) * 100) : null;
                        $rate{{ __('Text') }} = $overall{{ __('Rate') }} !== null ? $overall{{ __('Rate') }}.'%' : '{{ __('N/A') }}';
                    @endphp

                    {{-- Circle --}}
                    <div class="success-circle" style="border-color: {{ $overall{{ __('Rate') }} < 50 ? '#dc3545' : ($overall{{ __('Rate') }} < 80 ? '#fd7e14' : '#28a745') }}">
                        <div class="text-center">
                            <span class="fw-bold fs-2" style="color: {{ $overall{{ __('Rate') }} < 50 ? '#dc3545' : ($overall{{ __('Rate') }} < 80 ? '#fd7e14' : '#28a745') }}">
                                {{ $rate{{ __('Text') }} }}
                            </span>
                            <br>
                            <small class="text-muted" style="font-size: 12px;">({{ $totalParcels }} টি অর্ডার)</small>
                        </div>
                    </div>

                    {{-- {{ __('Rate') }} {{ __('Message') }} (Bangla) --}}
                    @if($overall{{ __('Rate') }} !== null)
                        @php
                            if ($overall{{ __('Rate') }} >= 90) {
                                $class = "status-green";
                                $msg = "✔ {{ __('bn_8704a028') }} - ঝুঁকিমুক্ত অবস্থা 😎";
                                $desc = "এই কাস্টমারের {{ __('bn_fbbc3031') }}তার {{ __('bn_f29420ce') }} চমৎকার। নিশ্চিন্তে অর্ডার প্রসেস করুন।";
                            }
                            elseif ($overall{{ __('Rate') }} >= 70) {
                                $class = "status-blue";
                                $msg = "ℹ️ ভালো - তবে সতর্ক থাকুন 🙂";
                                $desc = "{{ __('bn_fbbc3031') }}তার {{ __('bn_f29420ce') }} ভালো, তবে লোকেশন বা অন্য {{ __('Subject') }}গুলো চেক করে নিন।";
                            }
                            elseif ($overall{{ __('Rate') }} >= 40) {
                                $class = "status-orange";
                                $msg = "⚠ ঝুঁকি আছে – কনফার্ম {{ __('bn_290a7f61') }}ে নিন ⚠";
                                $desc = "রিটার্নের {{ __('bn_f29420ce') }} বেশি। অবশ্যই {{ __('bn_99838c8f') }} অগ্রিম নিন।";
                            }
                            else {
                                $class = "status-red";
                                $msg = "❗ {{ __('bn_8d38ebc7') }} – অর্ডার না নেওয়াই ভালো ❗";
                                $desc = "এই কাস্টমারের বেশিরভাগ পার্সেল ক্যানসেল {{ __('bn_290a7f61') }}। সাবধান!";
                            }
                        @endphp

                        <div class="status-box {{ $class }}">
                            <h5 class="fw-bold">{{ $msg }}</h5>
                            <p class="mb-0">{{ $desc }}</p>
                        </div>
                    @endif

                </div>
            </div>

            {{-- RIGHT: TABLE --}}
            <div class="col-md-8">
                <div class="card shadow-sm p-3">

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">

                            <thead>
                                <tr>
                                    <th>{{ __('bn_dec48f6e') }}</th>
                                    <th>{{ __('Orders') }}</th>
                                    <th>{{ __('bn_fbbc3031') }}</th>
                                    <th>{{ __('Cancelled') }}</th>
                                    <th>{{ __('bn_f29420ce') }}</th>
                                </tr>
                            </thead>

                            <tbody>

                                @php
                                    $api{{ __('Courier') }}s = $data['apis'] ?? [];
                                    $courierMapping = [
                                        '{{ __('Pathao') }}'  => ['key' => 'pathao', 'name' => '{{ __('Pathao') }}', 'logo' => 'pathao-logo.png'],
                                        'Redex'   => ['key' => 'redx', 'name' => '{{ __('RedX') }}', 'logo' => 'redx-logo.png'],
                                        'CarryBee' => ['key' => 'carrybee', 'name' => 'CarryBee', 'logo' => 'carrybee-logo.webp'],
                                    ];

                                    $myLogos = [
                                        'pathao'    => asset('public/assets/images/courier/pathao-logo.png'),
                                        'redx'      => asset('public/assets/images/courier/redx-logo.png'),
                                        'carrybee'  => asset('public/assets/images/courier/carrybee-logo.webp'),
                                    ];
                                @endphp

                                @foreach($courierMapping as $api{{ __('Name') }} => $mapped)

                                    @php
                                        $info = $api{{ __('Courier') }}s[$api{{ __('Name') }}] ?? [];
                                        $s = (int) ($info['{{ __('total') }}_delivered_parcels'] ?? 0);
                                        $c = (int) ($info['{{ __('total') }}_cancelled_parcels'] ?? 0);
                                        $t = (int) ($info['{{ __('total') }}_parcels'] ?? ($s + $c));
                                        $rate = $t > 0 ? round(($s/$t)*100) : 0;
                                        
                                        $logo = $myLogos[$mapped['key']] ?? null;
                                        $name = $mapped['name'];
                                    @endphp

                                    <tr>
                                        <td class="text-start ps-4">
                                            @if($logo)
                                                <img src="{{ $logo }}" class="courier-logo" alt="{{ $name }}">
                                            @endif
                                            <span class="fw-bold text-dark">{{ $name }}</span>
                                        </td>

                                        <td class="fw-bold">{{ $t }}</td>
                                        <td class="text-success fw-bold">{{ $s }}</td>
                                        <td class="text-danger fw-bold">{{ $c }}</td>

                                        <td>
                                            @php
                                                $border{{ __('Color') }} = $rate < 50 ? '#dc3545' : ($rate < 80 ? '#fd7e14' : '#28a745');
                                                $bg{{ __('Color') }}     = $rate < 50 ? '#ffeeee' : ($rate < 80 ? '#fff8e1' : '#e9ffe9');
                                                $text{{ __('Color') }}   = $rate < 50 ? '#dc3545' : ($rate < 80 ? '#fd7e14' : '#28a745');
                                            @endphp

                                            <div class="rate-circle" style="border-color: {{ $border{{ __('Color') }} }}; background: {{ $bg{{ __('Color') }} }}; color: {{ $text{{ __('Color') }} }}">
                                                {{ $rate }}%
                                            </div>

                                            <small class="d-block mt-1 text-muted" style="font-size: 11px;">
                                                @if($t == 0) তথ্য নেই
                                                @elseif($rate == 100) চমৎকার
                                                @elseif($rate >= 80) ভালো
                                                @elseif($rate >= 50) সাধারণ
                                                @else ঝুঁকিপূর্ণ
                                                @endif
                                            </small>
                                        </td>
                                    </tr>

                                @endforeach

                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </div>
        @endif

    </div>
</div>

@endsection