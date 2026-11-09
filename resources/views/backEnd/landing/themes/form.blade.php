@extends('backEnd.layouts.master')
@section('title', $theme ? __('Edit theme') : __('New theme'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">{{ $theme ? __('Edit theme') : __('New theme') }}</h4>
        <a href="{{ route('landing.themes.index') }}" class="btn btn-sm btn-light">{{ __('Back to themes') }}</a>
    </div>

    <div class="card" style="max-width: 640px">
        <form method="POST" action="{{ $action }}" class="card-body">
            @csrf
            <div class="mb-3">
                <label for="theme-name" class="form-label fw-semibold">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input id="theme-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $theme?->name) }}" required maxlength="100">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <span class="form-label fw-semibold d-block">{{ __('Colours') }}</span>
                @foreach(['primary' => 'Primary', 'accent' => 'Accent', 'background' => 'Background', 'text' => 'Text'] as $key => $label)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="theme-{{ $key }}" class="mb-0">{{ __($label) }}</label>
                        <input id="theme-{{ $key }}" type="color" name="{{ $key }}" class="form-control form-control-color @error($key) is-invalid @enderror"
                            value="{{ old($key, $colours[$key]) }}" required>
                    </div>
                    @error($key)<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Save theme') }}</button>
                <a href="{{ route('landing.themes.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
