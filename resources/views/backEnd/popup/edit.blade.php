@extends('backEnd.layouts.master')
@section('title', 'Edit Popup Campaign')

@section('content')

{{-- Custom CSS for Edit Page --}}
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
        font-size: 14px;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    /* Image Upload Area */
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
            <h4 class="fw-bold mb-0 text-dark"> {{ __('Edit Campaign') }} </h4>
            <small class="text-muted"> {{ __('Update your popup details and media') }} </small>
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

    <form action="{{ route('admin.popup.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="hidden_id" value="{{ $edit->id }}">

        <div class="row g-4">
            
            {{-- Left Column: Content Form --}}
            <div class="col-lg-8">
                <div class="card edit-card h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-edit me-2 text-primary"></i> {{ __('Text Content') }} </h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label"> {{ __('Campaign Title') }} </label>
                            <input type="text" class="form-control form-control-lg" name="title" value="{{ $edit->title }}" placeholder="Ex: Summer Sale (অপশনাল)">
                            <small class="text-muted" style="font-size: 11px;">শুধু ইমেইজ বানারের জন্য খালি রাখতে পারেন।</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"> {{ __('Description / Headline') }} </label>
                            <textarea class="form-control" name="description" rows="5" placeholder="Enter details...">{{ $edit->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Button Text') }} </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-mouse-pointer text-muted"></i></span>
                                    <input type="text" class="form-control" name="btn_text" value="{{ $edit->btn_text }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Button Link') }} </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-link text-muted"></i></span>
                                    <input type="text" class="form-control" name="link" value="{{ $edit->link }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> {{ __('Footer/End Text (Optional)') }} </label>
                            <input type="text" class="form-control" name="offer_end_text" value="{{ $edit->offer_end_text }}" placeholder="Ex: Offer ends soon...">
                        </div>

                    </div>
                </div>
            </div>

            {{-- Right Column: Image & Settings --}}
            <div class="col-lg-4">
                
                {{-- Publish Card --}}
                <div class="card edit-card mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-rocket me-2 text-success"></i> {{ __('Publish Settings') }} </h6>
                    </div>
                    <div class="card-body p-4">
                        <input type="hidden" name="status" value="0">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <label class="form-label mb-0 text-dark"> {{ __('Campaign Status') }} </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch" {{ $edit->status == 1 ? 'checked' : '' }} style="width: 3em; height: 1.5em; cursor: pointer;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Update Changes
                        </button>
                    </div>
                </div>

                {{-- Image Card --}}
                <div class="card edit-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="far fa-image me-2 text-info"></i> {{ __('Visual Media') }} </h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <label class="form-label d-block text-start mb-2"> {{ __('Popup Image') }} </label>
                        
                        <div class="image-upload-container" onclick="document.getElementById('editImageInput').click()">
                            <img id="editImgPreview" src="{{ url('public/'.$edit->image) }}" class="current-img-preview" alt="Popup Image">
                            
                            <div class="upload-btn-overlay">
                                <span class="badge bg-light text-dark border px-3 py-2" style="cursor: pointer;">
                                    <i class="fas fa-camera me-1"></i> {{ __('Change Image') }} </span>
                            </div>
                            
                            <input type="file" name="image" id="editImageInput" class="d-none" accept="image/*" onchange="previewEditImage(this)">
                        </div>
                        <div class="text-start mt-2">
                            @include('backEnd.media._picker_button', [
                                'field'   => 'image',
                                'label'   => 'Choose from Media Library',
                                'current' => (strpos($edit->image, 'uploads/media/') !== false) ? 'public/'.$edit->image : '',
                            ])
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

{{-- Reusable Media Gallery picker — "choose image from media library" --}}
@include('backEnd.media._picker')

{{-- JavaScript for Image Preview --}}
<script>
    function previewEditImage(input) {
        // A real file upload takes precedence over the media picker
        if (input.files && input.files[0]) {
            document.getElementById('image_url').value = '';
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editImgPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // When an image is chosen from the Media Library, clear the file input
    // and refresh the preview with the media URL.
    document.getElementById('image_url').addEventListener('change', function () {
        if (this.value) {
            document.getElementById('editImageInput').value = '';
            var src = this.value;
            // stored as 'public/uploads/media/...' -> build absolute URL
            if (src.indexOf('public/') === 0) {
                src = window.location.origin + '/' + src;
            }
            document.getElementById('editImgPreview').src = src;
        }
    });
</script>

@endsection