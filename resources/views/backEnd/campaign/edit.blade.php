@extends('backEnd.layouts.master')
@section('title','Landing Page Edit')

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
    .review-thumb-wrap { display: inline-block; position: relative; margin-right: .5rem; margin-bottom: .5rem; }
    .review-thumb-wrap img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
    .review-thumb-wrap .del { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #ef4444; color: #fff; font-size: 11px; line-height: 20px; text-align: center; text-decoration: none; }
    /* Status switch */
    .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; transition: .3s; border-radius: 26px; }
    .switch .slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
    .switch input:checked + .slider { background: #22c55e; }
    .switch input:checked + .slider:before { transform: translateX(22px); }
    .sec-toggle-row { display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .9rem; margin-bottom: .6rem; background: #f8fafc; }
    .sec-toggle-row .sec-label { font-weight: 600; font-size: .84rem; color: #334155; }
    /* Collapsible template section */
    .lp-collapsible .lp-collapsible-head { cursor: pointer; }
    .lp-collapsible .lp-collapsible-toggle { display: inline-flex; align-items: center; gap: .5rem; }
    .lp-collapsible .lp-collapsible-toggle i { color: #4f46e5; }
    .lp-collapsible .lp-collapse-arrow { transition: transform .25s ease; }
    .lp-collapsible.open .lp-collapse-arrow { transform: rotate(180deg); }
    .lp-collapsible .lp-section-switch { cursor: pointer; }
    .lp-collapsible .lp-section-switch input { pointer-events: none; }
    .lp-header-right { gap: .5rem; }
    .lp-number-badge { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #4f46e5; color: #fff; font-size: .72rem; font-weight: 700; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0"><i class="fa fa-pencil-square-o mr-1"></i> Edit Landing Page</h4>
                <div>
                    <a href="{{ route('campaign.create') }}" class="btn btn-outline-primary rounded-pill btn-sm mr-1"><i class="fa fa-plus mr-1"></i> Create</a>
                    <a href="{{ route('campaign.index') }}" class="btn btn-primary rounded-pill btn-sm"><i class="fa fa-list mr-1"></i> Manage</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ════════ FORM ════════ --}}
        <div class="col-lg-7 col-xl-7">
            <form action="{{ route('campaign.update') }}" method="POST" class="campaign-form" data-parsley-validate="" enctype="multipart/form-data">
                @csrf
                <input type="hidden" value="{{ $edit_data->id }}" name="hidden_id">

                {{-- 1️⃣ Basic Info (no switch — always shown) --}}
                <div class="card lp-section-card">
                    <div class="card-header"><i class="fa fa-info-circle"></i> <span class="lp-number-badge">1</span> Basic Info</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">Landing Page Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name }}" id="name" required>
                                    @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label for="status" class="d-block form-label">Status</label>
                                    <label class="switch mb-0">
                                        <input type="checkbox" value="1" name="status" @if($edit_data->status==1)checked @endif>
                                        <span class="slider"></span>
                                    </label>
                                    @error('status')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="deadline" class="form-label">Offer Deadline</label>
                                    <input type="datetime-local" class="form-control" name="deadline" value="{{ $edit_data->deadline }}" id="deadline">
                                    <div class="field-hint">Countdown timer on the landing page.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="banner_title" class="form-label">Banner Title</label>
                                    <input type="text" class="form-control" name="banner_title" value="{{ $edit_data->banner_title }}" id="banner_title">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2️⃣ Hero --}}
                @component('backEnd.campaign._template_section', ['key'=>'hero','title'=>'Hero Section','icon'=>'fa-rocket','visible'=>$section_config['hero']['visible'] ?? true])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[hero_eyebrow]" value="{{ $labels['hero_eyebrow'] ?? '' }}" placeholder="Limited time offer">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Hero Image</label>
                                <input type="file" class="form-control form-control-sm" name="image_one" id="image_one" data-current="{{ $edit_data->image_one }}">
                                @include('backEnd.media._picker_button', [
                                    'field'   => 'image_one',
                                    'label'   => 'Choose from Media Library',
                                    'current' => (strpos($edit_data->image_one ?? '', 'uploads/media/') !== false) ? $edit_data->image_one : '',
                                ])
                                <img class="img-preview-thumb mt-1" data-thumb-for="image_one" src="{{ asset($edit_data->image_one) }}" alt="">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Top Title (Left)</label>
                                <input type="text" class="form-control" name="top_title_1" value="{{ $edit_data->top_title_1 }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Top Title (Highlight)</label>
                                <input type="text" class="form-control" name="top_title_2" value="{{ $edit_data->top_title_2 }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label class="form-label">Short Description</label>
                                <textarea name="short_description" rows="3" class="summernote form-control">{{ $edit_data->short_description }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">CTA — Order</label>
                                <input type="text" class="form-control form-control-sm" name="labels[hero_cta_order]" value="{{ $labels['hero_cta_order'] ?? '' }}" placeholder="Order Now">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">CTA — Details</label>
                                <input type="text" class="form-control form-control-sm" name="labels[hero_cta_details]" value="{{ $labels['hero_cta_details'] ?? '' }}" placeholder="How It Works">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Trust — Ends Soon</label>
                                <input type="text" class="form-control form-control-sm" name="labels[hero_trust_ends]" value="{{ $labels['hero_trust_ends'] ?? '' }}" placeholder="Offer ends soon">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Trust — COD</label>
                                <input type="text" class="form-control form-control-sm" name="labels[hero_trust_cod]" value="{{ $labels['hero_trust_cod'] ?? '' }}" placeholder="Cash on Delivery">
                            </div>
                        </div>
                    </div>
                @endcomponent

                {{-- 3️⃣ Problem --}}
                @component('backEnd.campaign._template_section', ['key'=>'problem','title'=>'Problem','icon'=>'fa-exclamation-triangle','visible'=>$section_config['problem']['visible'] ?? true])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[problem_eyebrow]" value="{{ $labels['problem_eyebrow'] ?? '' }}" placeholder="The problem">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[problem_heading]" value="{{ $labels['problem_heading'] ?? '' }}" placeholder="Slow Wi-Fi is ruining your connected life">
                            </div>
                        </div>
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$problem, 'name'=>'problem', 'title'=>'Problem', 'fields'=>['num'=>['label'=>'Number','placeholder'=>'01','type'=>'text'],'title'=>['label'=>'Title','type'=>'text'],'text'=>['label'=>'Text','type'=>'textarea']]])
                @endcomponent

                {{-- 4️⃣ Solution --}}
                @component('backEnd.campaign._template_section', ['key'=>'solution','title'=>'Solution','icon'=>'fa-check-circle','visible'=>$section_config['solution']['visible'] ?? true])
                    <div class="form-group mb-3">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control form-control-sm" name="labels[solution_eyebrow]" value="{{ $labels['solution_eyebrow'] ?? '' }}" placeholder="The solution">
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$solution, 'name'=>'solution', 'title'=>'Solution', 'fields'=>['icon'=>['label'=>'Icon','placeholder'=>'✨ or fa-star','type'=>'text'],'title'=>['label'=>'Title','type'=>'text'],'text'=>['label'=>'Text','type'=>'textarea']]])
                @endcomponent

                {{-- 5️⃣ Features --}}
                @component('backEnd.campaign._template_section', ['key'=>'features','title'=>'Features','icon'=>'fa-star','visible'=>$section_config['features']['visible'] ?? true,'badge'=>'add / remove'])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[features_eyebrow]" value="{{ $labels['features_eyebrow'] ?? '' }}" placeholder="What's inside">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="heading_2" value="{{ $edit_data->heading_2 }}">
                            </div>
                        </div>
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$features, 'name'=>'features', 'title'=>'Feature', 'fields'=>['icon'=>['label'=>'Icon','placeholder'=>'✨ or fa-truck','type'=>'text'],'image'=>['label'=>'Image','type'=>'file'],'title'=>['label'=>'Title','type'=>'text'],'text'=>['label'=>'Text','type'=>'textarea']]])
                @endcomponent

                {{-- 6️⃣ Benefits --}}
                @component('backEnd.campaign._template_section', ['key'=>'benefits','title'=>'Benefits','icon'=>'fa-heart','visible'=>$section_config['benefits']['visible'] ?? true])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[benefits_eyebrow]" value="{{ $labels['benefits_eyebrow'] ?? '' }}" placeholder="How life changes">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[benefits_heading]" value="{{ $labels['benefits_heading'] ?? '' }}" placeholder="Faster internet. A calmer connected home">
                            </div>
                        </div>
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$benefits, 'name'=>'benefits', 'title'=>'Benefit', 'fields'=>['icon'=>['label'=>'Icon','placeholder'=>'✨ or fa-moon','type'=>'text'],'title'=>['label'=>'Title','type'=>'text'],'text'=>['label'=>'Text','type'=>'textarea']]])
                @endcomponent

                {{-- 7️⃣ Product Images / Videos --}}
                @component('backEnd.campaign._template_section', ['key'=>'media','title'=>'Product Images / Videos','icon'=>'fa-image','visible'=>$section_config['media']['visible'] ?? true])
                    <div class="form-group mb-3">
                        <label class="form-label">Section Heading</label>
                        <input type="text" class="form-control form-control-sm" name="labels[media_heading]" value="{{ $labels['media_heading'] ?? '' }}" placeholder="A closer look">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-group">
                                <label class="form-label">Banner Image</label>
                                <input type="file" class="form-control" name="banner" data-current="{{ $edit_data->banner }}">
                                @include('backEnd.media._picker_button', [
                                    'field'   => 'banner',
                                    'label'   => 'Choose from Media Library',
                                    'current' => (strpos($edit_data->banner ?? '', 'uploads/media/') !== false) ? $edit_data->banner : '',
                                ])
                                <img class="img-preview-thumb" data-thumb-for="banner" src="{{ asset($edit_data->banner) }}" alt="">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-group">
                                <label class="form-label">Image Two</label>
                                <input type="file" class="form-control" name="image_two" data-current="{{ $edit_data->image_two }}">
                                @include('backEnd.media._picker_button', [
                                    'field'   => 'image_two',
                                    'label'   => 'Choose from Media Library',
                                    'current' => (strpos($edit_data->image_two ?? '', 'uploads/media/') !== false) ? $edit_data->image_two : '',
                                ])
                                <img class="img-preview-thumb" data-thumb-for="image_two" src="{{ asset($edit_data->image_two) }}" alt="">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-group">
                                <label class="form-label">Image Three</label>
                                <input type="file" class="form-control" name="image_three" data-current="{{ $edit_data->image_three }}">
                                @include('backEnd.media._picker_button', [
                                    'field'   => 'image_three',
                                    'label'   => 'Choose from Media Library',
                                    'current' => (strpos($edit_data->image_three ?? '', 'uploads/media/') !== false) ? $edit_data->image_three : '',
                                ])
                                <img class="img-preview-thumb" data-thumb-for="image_three" src="{{ asset($edit_data->image_three) }}" alt="">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label class="form-label">YouTube Video URL / ID</label>
                                <input type="text" class="form-control" name="video" value="{{ $edit_data->video }}">
                                <div class="field-hint">Paste the link or just the 11-character video ID.</div>
                            </div>
                        </div>
                    </div>
                @endcomponent

                {{-- 8️⃣ Offer --}}
                @component('backEnd.campaign._template_section', ['key'=>'offer','title'=>'Offer','icon'=>'fa-tag','visible'=>$section_config['offer']['visible'] ?? true])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[offer_eyebrow]" value="{{ $labels['offer_eyebrow'] ?? '' }}" placeholder="Limited time">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[offer_heading]" value="{{ $labels['offer_heading'] ?? '' }}" placeholder="অর্ডার করতে চাইলে নিচের ফর্মটি পূরণ করুন">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Note</label>
                                <input type="text" class="form-control" name="note" value="{{ $edit_data->note }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Deadline</label>
                                <input type="datetime-local" class="form-control" name="deadline" value="{{ $edit_data->deadline }}">
                                <div class="field-hint">Countdown timer for the offer.</div>
                            </div>
                        </div>
                    </div>
                @endcomponent

                {{-- 9️⃣ Customer Review --}}
                @component('backEnd.campaign._template_section', ['key'=>'review','title'=>'Customer Review','icon'=>'fa-comments','visible'=>$section_config['review']['visible'] ?? true,'badge'=>'text + image'])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[review_eyebrow]" value="{{ $labels['review_eyebrow'] ?? '' }}" placeholder="What people say">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[reviews_heading]" value="{{ $labels['reviews_heading'] ?? '' }}" placeholder="3,240 people can't be wrong">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Review / Offer Text <span class="text-muted small">(hero rating, e.g. 4.9 (3,240))</span></label>
                        <input type="text" class="form-control @error('review') is-invalid @enderror" name="review" value="{{ $edit_data->review }}" required>
                        @error('review')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$reviews, 'name'=>'reviews', 'title'=>'Review', 'fields'=>['name'=>['label'=>'Name','placeholder'=>'Maya R.','type'=>'text'],'rating'=>['label'=>'Stars','placeholder'=>'5','type'=>'text'],'text'=>['label'=>'Review','type'=>'textarea']]])
                    <hr class="my-3">
                    <label class="form-label">Review Images (Slider / Gallery)</label>
                    <div class="d-flex flex-wrap align-items-center mb-2">
                        @foreach($edit_data->images as $image)
                        <div class="review-thumb-wrap">
                            <img src="{{ asset($image->image) }}" alt="" data-review-src="{{ asset($image->image) }}">
                            <a href="{{ route('campaign.image.destroy',['id'=>$image->id]) }}" class="del" title="Remove"><i class="fa fa-close"></i></a>
                        </div>
                        @endforeach
                    </div>
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
                @endcomponent

                {{-- 🔟 Trust Badges --}}
                @component('backEnd.campaign._template_section', ['key'=>'trust','title'=>'Trust Badges','icon'=>'fa-shield','visible'=>$section_config['trust']['visible'] ?? true])
                    <div class="form-group mb-3">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control form-control-sm" name="labels[trust_eyebrow]" value="{{ $labels['trust_eyebrow'] ?? '' }}" placeholder="Why shop with us">
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$trust, 'name'=>'trust', 'title'=>'Trust Badge', 'fields'=>['icon'=>['label'=>'Icon','placeholder'=>'✨ or fa-lock','type'=>'text'],'text'=>['label'=>'Text','type'=>'text']]])
                @endcomponent

                {{-- 1️⃣1️⃣ FAQ --}}
                @component('backEnd.campaign._template_section', ['key'=>'faq','title'=>'FAQ','icon'=>'fa-question-circle','visible'=>$section_config['faq']['visible'] ?? true,'badge'=>'q + a loop'])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[faq_eyebrow]" value="{{ $labels['faq_eyebrow'] ?? '' }}" placeholder="Questions">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[faq_heading]" value="{{ $labels['faq_heading'] ?? '' }}" placeholder="Everything you're wondering">
                            </div>
                        </div>
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$faq, 'name'=>'faq', 'title'=>'FAQ', 'fields'=>['q'=>['label'=>'Question','type'=>'text'],'a'=>['label'=>'Answer','type'=>'textarea']]])
                @endcomponent

                {{-- 1️⃣2️⃣ Checkout / Order Form --}}
                @component('backEnd.campaign._template_section', ['key'=>'order','title'=>'Checkout / Order Form','icon'=>'fa-shopping-cart','visible'=>$section_config['order']['visible'] ?? true])
                    <div class="form-group mb-3">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control form-control-sm" name="labels[order_eyebrow]" value="{{ $labels['order_eyebrow'] ?? '' }}" placeholder="Checkout">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Products <span class="text-danger">*</span></label>
                        <select class="select2 form-control @error('product_id') is-invalid @enderror" name="product_id[]" multiple="multiple" data-placeholder="Choose products for this campaign..." required>
                            @foreach($products as $value)
                                <option value="{{ $value->id }}"
                                    {{ $value->id == $edit_data->product_id || in_array($value->id, $select_products->pluck('id')->toArray()) ? 'selected' : '' }}>
                                    {{ $value->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        <div class="field-hint mt-1">First selected product is the campaign's primary product; the rest appear on the landing page.</div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Billing Details</label>
                        <input type="text" class="form-control" name="billing_details" value="{{ $edit_data->billing_details }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Select Product</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_select]" value="{{ $labels['form_select'] ?? '' }}" placeholder="Select your product">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Information</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_info]" value="{{ $labels['form_info'] ?? '' }}" placeholder="Your information">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Submit</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_submit]" value="{{ $labels['form_submit'] ?? '' }}" placeholder="অর্ডার কনফার্ম করুন">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Summary</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_summary]" value="{{ $labels['form_summary'] ?? '' }}" placeholder="Order summary">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Delivery Charge</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_delivery]" value="{{ $labels['form_delivery'] ?? '' }}" placeholder="Delivery Charge">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="form-label small">Form — Total</label>
                                <input type="text" class="form-control form-control-sm" name="labels[form_total]" value="{{ $labels['form_total'] ?? '' }}" placeholder="Total">
                            </div>
                        </div>
                    </div>
                @endcomponent

                {{-- 1️⃣3️⃣ CTA --}}
                @component('backEnd.campaign._template_section', ['key'=>'cta','title'=>'CTA','icon'=>'fa-bullhorn','visible'=>$section_config['cta']['visible'] ?? true])
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control form-control-sm" name="labels[cta_eyebrow]" value="{{ $labels['cta_eyebrow'] ?? '' }}" placeholder="Last call">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Section Heading</label>
                                <input type="text" class="form-control form-control-sm" name="labels[cta_heading]" value="{{ $labels['cta_heading'] ?? '' }}" placeholder="Stop buffering. Start streaming fast">
                            </div>
                        </div>
                    </div>
                    @include('backEnd.campaign._loop_rows', ['rows'=>$cta, 'name'=>'cta', 'title'=>'CTA', 'fields'=>['icon'=>['label'=>'Icon','placeholder'=>'✨','type'=>'text'],'title'=>['label'=>'Title','type'=>'text'],'text'=>['label'=>'Text','type'=>'textarea']]])
                    <hr class="my-3">
                    <h6 class="text-uppercase fw-bold" style="font-size:.75rem;color:#6366f1;margin-bottom:.5rem;">Navigation &amp; Footer</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Nav — Features</label>
                                <input type="text" class="form-control form-control-sm" name="labels[nav_features]" value="{{ $labels['nav_features'] ?? '' }}" placeholder="Features">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Nav — Reviews</label>
                                <input type="text" class="form-control form-control-sm" name="labels[nav_reviews]" value="{{ $labels['nav_reviews'] ?? '' }}" placeholder="Reviews">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Nav — FAQ</label>
                                <input type="text" class="form-control form-control-sm" name="labels[nav_faq]" value="{{ $labels['nav_faq'] ?? '' }}" placeholder="FAQ">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Nav — Order</label>
                                <input type="text" class="form-control form-control-sm" name="labels[nav_order]" value="{{ $labels['nav_order'] ?? '' }}" placeholder="Order">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Nav — CTA</label>
                                <input type="text" class="form-control form-control-sm" name="labels[nav_cta]" value="{{ $labels['nav_cta'] ?? '' }}" placeholder="Order Now">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Sticky — Order</label>
                                <input type="text" class="form-control form-control-sm" name="labels[sticky_order]" value="{{ $labels['sticky_order'] ?? '' }}" placeholder="অর্ডার করুন">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Sticky — COD</label>
                                <input type="text" class="form-control form-control-sm" name="labels[sticky_cod]" value="{{ $labels['sticky_cod'] ?? '' }}" placeholder="Cash on Delivery">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="form-label small">Footer — Rights</label>
                                <input type="text" class="form-control form-control-sm" name="labels[footer_rights]" value="{{ $labels['footer_rights'] ?? '' }}" placeholder="All rights reserved">
                            </div>
                        </div>
                    </div>
                @endcomponent

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success px-4 rounded-pill"><i class="fa fa-check mr-1"></i> Update Campaign</button>
                    <a href="{{ route('campaign.index') }}" class="btn btn-light px-4 rounded-pill">Cancel</a>
                </div>
            </form>
        </div>

        {{-- ════════ LIVE PREVIEW ════════ --}}
        <div class="col-lg-5 col-xl-5">
            @include('backEnd.campaign._preview', ['preview_products' => $preview_products, 'preview_url' => url('/campaign/' . ($edit_data->slug ?? '')), 'preview_slug' => $edit_data->slug ?? ''])
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
        $('.select2').select2();

        // ---- Collapsible template sections ----
        $(document).on('click', '.lp-collapsible-head', function (e) {
            if ($(e.target).closest('.lp-section-switch').length) return; // don't collapse when toggling switch
            var card = $(this).closest('.lp-collapsible');
            card.toggleClass('open');
            card.find('.lp-collapsible-body').slideToggle(200);
        });
        // Open all by default (except can collapse manually)
        $('.lp-collapsible').addClass('open').find('.lp-collapsible-body').show();

        // ---- Generic loop rows (add / remove) ----
        $(document).on('click', '.loop-row-add', function () {
            var loop  = $(this).data('loop');
            var title = $(this).data('title');
            var fields = $(this).data('fields') || [];
            var container = $(this).closest('.card-body').find('.loop-rows[data-loop="' + loop + '"]');
            var idx = container.find('.loop-row').length;

            var html = '<div class="loop-row sec-config-card">' +
                '<div class="sec-config-head">' +
                    '<span class="badge-key">' + title + ' #' + (idx + 1) + '</span>' +
                    '<button type="button" class="btn btn-danger btn-sm loop-row-remove"><i class="fa fa-trash"></i></button>' +
                '</div><div class="row">';

            fields.forEach(function (f) {
                var field = f.key || 'field';
                if (f.type === 'textarea') {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<textarea class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" placeholder="' + (f.placeholder || '') + '" rows="2"></textarea></div>';
                } else if (f.type === 'file') {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<input type="file" class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" data-loop-file="' + loop + '_' + field + '">' +
                            '<input type="hidden" name="' + loop + '[' + idx + '][' + field + '_old]" value="">' +
                            '<img class="img-preview-thumb mt-1" data-loop-preview="' + loop + '_' + field + '" style="display:none;" alt=""></div>';
                } else {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<input type="text" class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" value="" placeholder="' + (f.placeholder || '') + '"></div>';
                }
            });

            html += '</div></div>';
            container.find('.loop-empty-hint').remove();
            container.append(html);
        });

        $(document).on('click', '.loop-row-remove', function () {
            $(this).closest('.loop-row').remove();
        });

        // Loop file image preview
        $(document).on('change', '[data-loop-file]', function () {
            var file = this.files && this.files[0];
            if (!file) return;
            var key = $(this).data('loop-file');
            var preview = document.querySelector('[data-loop-preview="' + key + '"]');
            var reader = new FileReader();
            reader.onload = function (e) { if (preview) { preview.src = e.target.result; preview.style.display = 'block'; } };
            reader.readAsDataURL(file);
        });

        // Review images increment
        $(".btn-increment").click(function () {
            var html = $(".clone").html();
            $(".increment").after(html);
        });
        $("body").on("click", ".btn-danger", function () {
            $(this).parents(".control-group").remove();
        });

        // local image thumbnails for file inputs
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
