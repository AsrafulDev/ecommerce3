@extends('backEnd.layouts.master')
@section('title', '{{ __('Facebook Page - Auto Post {{ __('Product') }}s') }}')

@section('content')
<div class="container-fluid py-3">
  <div class="row">
    <div class="col-12">
      <h4 class="fw-bold mb-3"><i class="{{ __('fe-facebook') }} text-primary me-2"></i> {{ __('Facebook Page - Auto Post {{ __('Product') }}s') }}</h4>
      <p class="text-muted">Configure your Facebook Page to auto-post or manually post products. {{ __('Use') }} {name}, {price}, {link}, {description} in post template.</p>
    </div>
  </div>

  <form action="{{ route('admin.facebook_page.save_settings') }}" method={{ __('"{{ __('POST') }}"') }}>
    @csrf

    <div class="card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label>{{ __('Page ID') }}</label>
            <input type="text" name="page_id" class="form-control" value="{{ $setting->page_id ?? '' }}" placeholder="{{ __('e.g. {{ __('1234567890') }}12345') }}" required>
            <small class="text-muted">Find in Page Settings → About</small>
          </div>
          <div class="col-md-6">
            <label>{{ __('Page {{ __('Access Token') }}') }}</label>
            <input type="password" name="page_access_token" class="form-control" value="{{ $setting->page_access_token ?? '' }}" placeholder="{{ __('Long-lived Page token') }}">
            <small class="text-muted">{{ __('Generate from') }} <a href="{{ __('https://') }}developers.facebook.com/tools/explorer/" target="_blank">{{ __('Graph API Explorer') }}</a> with pages_manage_posts, pages_read_engagement</small>
          </div>
          <div class="col-md-6">
            <label>{{ __('{{ __('Page {{ __('Name') }}') }} (optional)') }}</label>
            <input type="text" name="page_name" class="form-control" value="{{ $setting->page_name ?? '' }}" placeholder="{{ __('Your {{ __('Page {{ __('Name') }}') }}') }}">
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" name="auto_post_new_products" value="1" {{ ($setting->auto_post_new_products ?? false) ? 'checked' : '' }}>
              <label class="form-check-label">{{ __('Auto-post when new product is created') }}</label>
            </div>
          </div>
          <div class="col-12">
            <label>{{ __('Post Template') }}</label>
            <textarea name="post_template" class="form-control" rows="4" placeholder="Default: {{ __('New') }} {{ __('Product') }}! {name} - ৳{price}. Order: {link}">{{ $setting->post_template ?? "🛒 {{ __('New') }} {{ __('Product') }}!\n\n{name}\n\nPrice: ৳{price}\n\nOrder now: {link}" }}</textarea>
            <small class="text-muted">{{ __('Use') }}: {name}, {price}, {link}, {description}</small>
          </div>
        </div>
        <button type="{{ __('submit') }}" class="btn btn-primary mt-3"><i class="fe-save"></i>{{ __('Save') }}</button>
      </div>
    </div>
  </form>
</div>
@endsection
