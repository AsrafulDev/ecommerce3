@extends('backEnd.layouts.master')
@section('title', 'Edit Popup {{ __('Campaign') }}')

@section('content')

{{-- {{ __('Custom CSS') }} for Edit Page --}}
<style>
    .edit-card {
        background: #fff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
    }
    .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        font-size: {{ __('14px') }};
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    /* Image Upload {{ __('Area') }} */
    .image-upload-container {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: 10px;
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        text-align: center;
        padding: 10px;
        transition: all 0.3s;
    }
    .image-upload-container:hover {
        border-color: #6366f1;
        background: #eef2ff;
    }
    .current-img-preview {
        max-width: 100%;
        border-radius: 8px;
        display: block;
        margin: 0 auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .upload-btn-overlay {
        margin-top: 15px;
        display: inline-block;
    }
</style>

<div class="container-fluid py-4">
    
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">{{ __('Edit {{ __('Campaign') }}') }}</h4>
            <small class="text-muted">{{ __('Update your popup details and media') }}</small>
        </div>
        <a href="{{ route('admin.popup.index') }}" class="btn btn-light border text-muted fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.popup.update') }}" method={{ __('"{{ __('POST') }}"') }} enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="hidden_id" value="{{ $edit->id }}">

        <div class="row g-4">
            
            {{-- Left Column: Content Form --}}
            <div class="col-lg-8">
                <div class="card edit-card h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-edit me-2 text-primary"></i>{{ __('{{ __('Text') }} Content') }}</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label">{{ __('{{ __('Campaign') }} {{ __('Title') }}') }}</label>
                            <input type="text" class="form-control form-control-lg" name="title" value="{{ $edit->title }}" placeholder="Ex: Summer Sale (অপশনাল)">
                            <small class="text-muted" style="font-size: 11px;">{{ __('bn_36c5e04c') }}</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('Description / Headline') }}</label>
                            <textarea class="form-control" name="description" rows="5" placeholder="{{ __('Enter details...') }}">{{ $edit->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Button {{ __('Text') }}') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-mouse-pointer text-muted"></i></span>
                                    <input type="text" class="form-control" name="btn_text" value="{{ $edit->btn_text }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Button {{ __('Link') }}') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-link text-muted"></i></span>
                                    <input type="text" class="form-control" name="link" value="{{ $edit->link }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Footer/End {{ __('Text') }} {{ __('({{ __('Optional') }})') }}') }}</label>
                            <input type="text" class="form-control" name="offer_end_text" value="{{ $edit->offer_end_text }}" placeholder="{{ __('Ex: Offer ends soon...') }}">
                        </div>

                    </div>
                </div>
            </div>

            {{-- Right Column: Image & Settings --}}
            <div class="col-lg-4">
                
                {{-- {{ __('Publish') }} Card --}}
                <div class="card edit-card mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-rocket me-2 text-success"></i>{{ __('{{ __('Publish') }} Settings') }}</h6>
                    </div>
                    <div class="card-body p-4">
                        <input type="hidden" name="status" value="0">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <label class="form-label mb-0 text-dark">{{ __('{{ __('Campaign') }} {{ __('Status') }}') }}</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch" {{ $edit->status == 1 ? 'checked' : '' }} style="width: 3em; height: 1.5em; cursor: pointer;">
                            </div>
                        </div>
                        <button type="{{ __('submit') }}" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Update {{ __('Change') }}s
                        </button>
                    </div>
                </div>

                {{-- Image Card --}}
                <div class="card edit-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="far fa-image me-2 text-info"></i>{{ __('Visual {{ __('Media') }}') }}</h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <label class="form-label d-block text-start mb-2">{{ __('Popup Image') }}</label>
                        
                        <div class="image-upload-container" onclick="document.getElementById('editImageInput').click()">
                            <img id="editImg{{ __('Prev') }}iew" src="{{ url('public/'.$edit->image) }}" class="current-img-preview" alt="{{ __('Popup Image') }}">
                            
                            <div class="upload-btn-overlay">
                                <span class="badge bg-light text-dark border px-3 py-2" style="cursor: pointer;">
                                    <i class="fas fa-camera me-1"></i> {{ __('{{ __('Change') }} Image') }}
                                </span>
                            </div>
                            
                            <input type="file" name="image" id="editImageInput" class="d-none" accept="image/*" onchange="previewEditImage(this)">
                        </div>
                        <small class="text-muted d-block mt-3" style="font-size: 11px;">
                            Click the box to upload a new image. Recommended size: 600x400px.
                        </small>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- JavaScript for Image {{ __('Prev') }}iew --}}
<script>
    function previewEditImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editImg{{ __('Prev') }}iew').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

@endsection