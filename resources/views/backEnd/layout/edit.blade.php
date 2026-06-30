@extends('backEnd.layouts.master')
@section('title', $edit_data ? 'Edit Layout: ' . $edit_data->name : 'Create Layout')

@section('css')
<style>
    .form-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .section-title-pro {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        padding: 15px 25px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-label-pro {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .custom-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.2s;
        width: 100%;
    }
    .custom-input:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        outline: none;
    }
    .btn-save-pro {
        background: #0f172a;
        color: #fff;
        padding: 12px 35px;
        border-radius: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        transition: 0.3s;
    }
    .btn-save-pro:hover {
        background: #334155;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">
                @if($edit_data)
                    <i class="mdi mdi-view-dashboard me-2"></i> Edit Layout: {{ $edit_data->name }}
                @else
                    <i class="mdi mdi-view-dashboard-plus me-2"></i> Create {{ __('New') }} Layout
                @endif
            </h4>
            <a href="{{ route('layouts.index') }}" class="text-muted small">
                <i class="fe-arrow-left me-1"></i> Back to {{ __('Layouts') }}
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ $edit_data ? route('layouts.update') : route('layouts.store') }}" method={{ __('"{{ __('POST') }}"') }}>
                @csrf
                @if($edit_data)
                    <input type="hidden" name="id" value="{{ $edit_data->id }}">
                @endif

                <div class="form-card">
                    <div class="section-title-pro"><i class="mdi mdi-information-outline text-primary"></i> {{ __('Layout Details') }}</div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ __('Layout {{ __('{{ __('Name') }} *') }}') }}</label>
                                <input type="text" name="name" class="custom-input" value="{{ old('name', $edit_data->name ?? '') }}" required maxlength="100" placeholder="{{ __('e.g. Default Layout v2') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-pro">Active?</label>
                                <select name="is_active" class="custom-input">
                                    <option value="1" {{ old('is_active', $edit_data->is_active ?? true) ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                    <option value="0" {{ old('is_active', $edit_data->is_active ?? true) ? '' : 'selected' }}>{{ __('No') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-pro">Set as Default?</label>
                                <select name="is_default" class="custom-input">
                                    <option value="1" {{ old('is_default', $edit_data->is_default ?? false) ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                    <option value="0" {{ old('is_default', $edit_data->is_default ?? false) ? '' : 'selected' }}>{{ __('No') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label-pro">{{ __('{{ __('Note') }}s / Description') }}</label>
                                <textarea name="description" class="custom-input" rows="3" placeholder="{{ __('{{ __('Optional') }} description for this layout...') }}">{{ old('description', $edit_data->description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-5 mt-3">
                    <button type="{{ __('submit') }}" class="btn-save-pro">
                        <i class="mdi mdi-content-save-all me-2"></i>
                        {{ $edit_data ? 'Update Layout' : 'Create Layout' }}
                    </button>
                    <a href="{{ route('layouts.index') }}" class="btn btn-light rounded-pill px-4 ms-2 fw-bold">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
