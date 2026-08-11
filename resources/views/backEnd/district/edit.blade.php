@extends('backEnd.layouts.master')
@section('title', 'Edit District')

@section('css')
<style>
    .district-card { border: none; box-shadow: 0 0 35px 0 rgba(154,161,171,.15); border-radius: 10px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#333;"><i class="fe-map-pin me-1"></i> {{ __('Edit District / Area') }}</h4>
            <a href="{{ route('admin.district.index') }}" class="btn btn-light border fw-bold text-secondary"><i class="fe-arrow-left me-1"></i>{{ __('Back') }}</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card district-card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.district.update') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $district->id }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('District') }} <span class="text-danger">*</span></label>
                                <input type="text" name="district" class="form-control" value="{{ old('district', $district->district) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Area Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="area_name" class="form-control" value="{{ old('area_name', $district->area_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Mark for Charge Update') }}</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="charge_update_required" value="1"
                                        {{ old('charge_update_required', $district->charge_update_required) ? 'checked' : '' }}
                                        style="width:3em;height:1.5em;cursor:pointer;">
                                    <label class="form-check-label small text-muted">{{ __('Needs shipping-charge update') }}</label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary px-4"><i class="fe-check-circle me-1"></i>{{ __('Update District') }}</button>
                                <a href="{{ route('admin.district.index') }}" class="btn btn-light border">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
