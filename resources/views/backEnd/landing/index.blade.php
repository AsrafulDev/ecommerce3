@extends('backEnd.layouts.master')
@section('title', 'Landing Pages')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Landing Pages') }}</h4>
            <p class="text-muted small mb-0">{{ __('Each landing page uses one template and one product. Edit the text and images in place.') }}</p>
        </div>
        @can('landing-create')
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#landingCreate">
                <i class="fe-plus me-1"></i> {{ __('New landing page') }}
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Template') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Owner') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($landings as $landing)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $landing->name }}</strong><br>
                                <small class="text-muted">/landing/{{ $landing->slug }}</small>
                            </td>
                            <td>{{ \App\Services\Landing\TemplateRegistry::label($landing->template_key) }}</td>
                            <td>{{ $landing->product?->name ?? '—' }}</td>
                            <td>{{ $landing->creator?->name ?? '—' }}</td>
                            <td>
                                @if($landing->status)
                                    <span class="badge bg-success">{{ __('Live') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Draft') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('landing-edit')
                                    <a href="{{ route('landing.manage.edit', $landing->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No landing pages yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create popup: title, template, product. Everything else is set in the editor. --}}
@can('landing-create')
<div class="modal fade" id="landingCreate" tabindex="-1" aria-labelledby="landingCreateLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('landing.manage.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="landingCreateLabel">{{ __('New landing page') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="landing-name" class="form-label fw-semibold">{{ __('Landing title') }} <span class="text-danger">*</span></label>
                    <input id="landing-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="191">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <span class="form-label fw-semibold d-block">{{ __('Template') }} <span class="text-danger">*</span></span>
                    @foreach($templates as $key => $label)
                        <label class="landing-template-card d-block border rounded p-3 mb-2">
                            <input class="form-check-input me-2" type="radio" name="template_key" value="{{ $key }}" @checked($loop->first) required>
                            <strong>{{ $label }}</strong>
                            <span class="text-muted small d-block">{{ __('Serif headings, gold accents, soft cards.') }}</span>
                        </label>
                    @endforeach
                    @error('template_key')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-1">
                    <label for="landing-product" class="form-label fw-semibold">{{ __('Product') }} <span class="text-danger">*</span></label>
                    <select id="landing-product" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">{{ __('Choose a product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Create and open editor') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@if($errors->any())
@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('landingCreate');
        if (modal && window.bootstrap) new bootstrap.Modal(modal).show();
    });
</script>
@endsection
@endif
