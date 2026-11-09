@extends('backEnd.layouts.master')
@section('title', 'Single Layout')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Single Layout') }}</h4>
            <p class="text-muted small mb-0">{{ __('The design of the product detail page: colours, sections and the gallery side. The active layout is what customers see.') }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('single-layout.store') }}" class="d-flex flex-wrap gap-2 align-items-end">
                @csrf
                <div>
                    <label for="sl-new-name" class="form-label fw-semibold mb-1">{{ __('New layout') }}</label>
                    <input id="sl-new-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="100" placeholder="{{ __('Name') }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Create layout') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Layout') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($layouts as $layout)
                        <tr>
                            <td><strong>{{ $layout->name }}</strong></td>
                            <td>
                                @if($layout->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Not active') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('single-layout.edit', $layout->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Design') }}</a>
                                    @unless($layout->is_active)
                                        <form method="POST" action="{{ route('single-layout.activate', $layout->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Activate') }}</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('single-layout.duplicate', $layout->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Duplicate') }}</button>
                                    </form>
                                    @unless($layout->is_active)
                                        <form method="POST" action="{{ route('single-layout.destroy', $layout->id) }}" class="d-inline"
                                            onsubmit="return confirm('{{ __('Delete this layout?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
