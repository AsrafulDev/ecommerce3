@extends('backEnd.layouts.master')
@section('title','Landing Page Create')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
<style>
    .lp-section-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(15,23,42,.06); margin-bottom: 1.25rem; overflow: hidden; }
    .lp-section-card .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; font-weight: 700; font-size: .92rem; padding: .9rem 1.25rem; display: flex; align-items: center; gap: .5rem; }
    .lp-section-card .card-header i { color: #4f46e5; }
    .lp-section-card .card-body { padding: 1.25rem; }
    .lp-section-card .form-label { font-weight: 600; font-size: .82rem; color: #475569; }
    .lp-section-card .form-control, .lp-section-card .form-select { border-radius: 8px; border-color: #e2e8f0; }
    .lp-section-card .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 .2rem rgba(99,102,241,.12); }
    .img-preview-thumb { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: .4rem; background: #f8fafc; }
    .field-hint { font-size: .72rem; color: #94a3b8; margin-top: .25rem; }
    /* Section toggle switch */
    .switch { position: relative; display: inline-block; width: 48px; height: 26px; flex: none; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; transition: .3s; border-radius: 26px; }
    .switch .slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
    .switch input:checked + .slider { background: #22c55e; }
    .switch input:checked + .slider:before { transform: translateX(22px); }
    .sec-toggle-row { display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .9rem; margin-bottom: .6rem; background: #f8fafc; }
    .sec-toggle-row .sec-label { font-weight: 600; font-size: .84rem; color: #334155; }
    .sec-config-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; margin-bottom: .85rem; background: #fafafa; transition: border-color .2s; }
    .sec-config-card:focus-within { border-color: #6366f1; background: #fff; }
    .sec-config-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
    .sec-config-head .badge-key { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; background: #e2e8f0; color: #475569; padding: .2rem .5rem; border-radius: 6px; font-weight: 700; }
    .sec-config-card .form-control-sm { border-radius: 6px; font-size: .8rem; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0"><i class="fa fa-file-text-o mr-1"></i> Landing Page Create</h4>
                <div>
                    <a href="{{ route('campaign.create') }}" class="btn btn-outline-primary rounded-pill btn-sm mr-1"><i class="fa fa-plus mr-1"></i> Create</a>
                    <a href="{{ route('campaign.index') }}" class="btn btn-primary rounded-pill btn-sm"><i class="fa fa-list mr-1"></i> Manage</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ════════ FORM ════════ --}}
        <div class="col-xl-7">
            <form action="{{ route('campaign.store') }}" method="POST" class="campaign-form" data-parsley-validate="" enctype="multipart/form-data">
                @csrf

                {{-- 🔤 Basic Info --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-info-circle"></i> Basic Info</div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Landing Page Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" required placeholder="e.g. Ramadan Mega Offer">
                            @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="deadline" class="form-label">Offer Deadline</label>
                                    <input type="datetime-local" class="form-control @error('deadline') is-invalid @enderror" name="deadline" value="{{ old('deadline') }}" id="deadline">
                                    @error('deadline')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                    <div class="field-hint">Countdown timer on the landing page.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="banner_title" class="form-label">Banner Title</label>
                                    <input type="text" class="form-control @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ old('banner_title') }}" id="banner_title">
                                    @error('banner_title')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 🎯 Top Bar / Headings --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-heading"></i> Top Bar &amp; Headings</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="top_title_1" class="form-label">Top Title (Left)</label>
                                    <input type="text" class="form-control" name="top_title_1" value="{{ old('top_title_1') }}" id="top_title_1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="top_title_2" class="form-label">Top Title (Highlight)</label>
                                    <input type="text" class="form-control" name="top_title_2" value="{{ old('top_title_2') }}" id="top_title_2">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="heading_1" class="form-label">Heading 1</label>
                                    <input type="text" class="form-control" name="heading_1" value="{{ old('heading_1') }}" id="heading_1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="heading_2" class="form-label">Heading 2</label>
                                    <input type="text" class="form-control" name="heading_2" value="{{ old('heading_2') }}" id="heading_2">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="heading_3" class="form-label">Heading 3</label>
                                    <input type="text" class="form-control" name="heading_3" value="{{ old('heading_3') }}" id="heading_3">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="heading_4" class="form-label">Heading 4 (Contact)</label>
                                    <input type="text" class="form-control" name="heading_4" value="{{ old('heading_4') }}" id="heading_4">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ⭐ Features & Video --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-star"></i> Features &amp; Video</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="feature_1" class="form-label">Feature 1</label>
                                    <input type="text" class="form-control" name="feature_1" value="{{ old('feature_1') }}" id="feature_1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="feature_2" class="form-label">Feature 2</label>
                                    <input type="text" class="form-control" name="feature_2" value="{{ old('feature_2') }}" id="feature_2">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="video" class="form-label">YouTube Video URL / ID</label>
                                    <input type="text" class="form-control" name="video" value="{{ old('video') }}" id="video" placeholder="https://www.youtube.com/watch?v=...">
                                    <div class="field-hint">Paste the link or just the 11-character video ID.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 🖼️ Images --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-image"></i> Images</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="banner" class="form-label">Banner Image</label>
                                    <input type="file" class="form-control @error('banner') is-invalid @enderror" name="banner" id="banner" data-current="">
                                    @include('backEnd.media._picker_button', ['field' => 'banner', 'label' => 'Choose from Media Library'])
                                    <img class="img-preview-thumb" data-thumb-for="banner" style="display:none;" alt="">
                                    @error('banner')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="image_one" class="form-label">Image One</label>
                                    <input type="file" class="form-control @error('image_one') is-invalid @enderror" name="image_one" id="image_one" data-current="">
                                    @include('backEnd.media._picker_button', ['field' => 'image_one', 'label' => 'Choose from Media Library'])
                                    <img class="img-preview-thumb" data-thumb-for="image_one" style="display:none;" alt="">
                                    @error('image_one')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="image_two" class="form-label">Image Two</label>
                                    <input type="file" class="form-control" name="image_two" id="image_two" data-current="">
                                    @include('backEnd.media._picker_button', ['field' => 'image_two', 'label' => 'Choose from Media Library'])
                                    <img class="img-preview-thumb" data-thumb-for="image_two" style="display:none;" alt="">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="image_three" class="form-label">Image Three</label>
                                    <input type="file" class="form-control" name="image_three" id="image_three" data-current="">
                                    @include('backEnd.media._picker_button', ['field' => 'image_three', 'label' => 'Choose from Media Library'])
                                    <img class="img-preview-thumb" data-thumb-for="image_three" style="display:none;" alt="">
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Review Images (Gallery)</label>
                                <div class="input-group control-group increment mb-2">
                                    <input type="file" name="image[]" class="form-control" data-current="">
                                    <div class="input-group-btn">
                                        <button class="btn btn-success btn-increment" type="button"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="clone" style="display: none;">
                                    <div class="control-group input-group mb-2">
                                        <input type="file" name="image[]" class="form-control" data-current="">
                                        <div class="input-group-btn">
                                            <button class="btn btn-danger" type="button"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 🛒 Products --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-cubes"></i> Products <span class="text-danger">*</span></div>
                    <div class="card-body">
                        <select class="select2 form-control @error('product_id') is-invalid @enderror" name="product_id[]" multiple="multiple" data-placeholder="Choose products for this campaign..." required>
                            @foreach($products as $value)
                                <option value="{{ $value->id }}">{{ $value->name }}</option>
                            @endforeach
                        </select>
                        @error('product_id')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        <div class="field-hint mt-1">First selected product is the campaign's primary product; the rest appear on the landing page.</div>
                    </div>
                </div>

                {{-- �️ Section Visibility --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-eye-slash"></i> Section Visibility <span class="badge bg-light text-muted ms-auto">show / hide</span></div>
                    <div class="card-body">
                        <div class="row">
                            @foreach(\App\Models\Campaign::SECTIONS as $key => $label)
                            <div class="col-md-6">
                                <div class="sec-toggle-row">
                                    <span class="sec-label">{{ $label }}</span>
                                    <label class="switch mb-0" title="Show / hide this section">
                                        <input type="checkbox" class="section-toggle" name="sections[]" value="{{ $key }}" {{ ($default_sections[$key] ?? true) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="field-hint">Toggle each landing page section on/off. The live preview updates instantly.</div>
                    </div>
                </div>

                {{-- �📝 Review & Description --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-pencil-square-o"></i> Review &amp; Description</div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="review" class="form-label">Review / Offer Text <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('review') is-invalid @enderror" name="review" value="{{ old('review') }}" id="review" required>
                            @error('review')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea name="short_description" rows="6" class="summernote form-control">{{ old('short_description') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label for="description" class="form-label">Full Description</label>
                            <textarea name="description" rows="6" class="summernote form-control">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 📋 Order Form / Note --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-file-text-o"></i> Order Form &amp; Extra</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="note" class="form-label">Note</label>
                                    <input type="text" class="form-control" name="note" value="{{ old('note') }}" id="note">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="billing_details" class="form-label">Billing Details</label>
                                    <input type="text" class="form-control" name="billing_details" value="{{ old('billing_details') }}" id="billing_details">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4 rounded-pill"><i class="fa fa-check mr-1"></i> Create Campaign</button>
                            <a href="{{ route('campaign.index') }}" class="btn btn-light px-4 rounded-pill">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- ════════ LIVE PREVIEW ════════ --}}
        <div class="col-xl-5">
            @include('backEnd.campaign._preview', ['preview_products' => $preview_products])
        </div>
    </div>
</div>

{{-- Reusable Media Gallery picker — "choose image from media library" --}}
@include('backEnd.media._picker')
@endsection

@section('script')
<script src="{{ asset('public/backEnd/') }}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-validation.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-advanced.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-pickers.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/summernote/summernote-lite.min.js"></script>

<script>
    $(function () {
        // select2
        $('.select2').select2();

        // dynamic review-image rows
        $(".btn-increment").click(function () {
            var html = $(".clone").html();
            $(".increment").after(html);
        });
        $("body").on("click", ".btn-danger", function () {
            $(this).parents(".control-group").remove();
        });

        // small local image thumbnails (file inputs)
        $('input[type="file"]').on('change', function () {
            var file = this.files && this.files[0];
            if (!file) return;
            var reader = new FileReader();
            var thumb = document.querySelector('[data-thumb-for="' + this.name + '"]');
            reader.onload = function (e) {
                if (thumb) { thumb.src = e.target.result; thumb.style.display = 'block'; }
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@endsection
