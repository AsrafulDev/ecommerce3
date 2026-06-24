@extends('backEnd.layouts.master')
@section('title','Layout Manager')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
    .layout-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        padding: 20px 24px;
        transition: all 0.3s ease;
        margin-bottom: 16px;
    }
    .layout-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .layout-card.active-layout {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59,130,246,0.15), 0 8px 30px rgba(0,0,0,0.1);
    }
    .section-preview-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .section-preview-list .badge-section {
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        padding: 3px 10px;
        border-radius: 6px;
        font-weight: 500;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="mdi mdi-view-dashboard me-2"></i> Layout Manager</h4>
            <span class="text-muted small">Create and manage homepage section layouts</span>
        </div>
        <div>
            <a href="{{ route('themes.index') }}" class="btn btn-outline-secondary rounded-pill px-3 me-2">
                <i class="mdi mdi-palette me-1"></i> Themes
            </a>
            <a href="{{ route('layouts.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fe-plus me-1"></i> Create Layout
            </a>
        </div>
    </div>

    {{-- Active Layout Banner --}}
    @if($activeLayout)
    <div class="alert alert-info d-flex align-items-center gap-3 rounded-4 border-0 shadow-sm mb-4" style="background:#eff6ff;">
        <i class="mdi mdi-check-circle text-primary fs-3"></i>
        <div>
            <strong class="text-dark">Active Layout:</strong>
            <span class="fw-bold ms-1">{{ $activeLayout->name }}</span>
            <span class="text-muted ms-2">({{ $activeLayout->sections_count }} sections)</span>
        </div>
        <a href="{{ route('layouts.builder', $activeLayout->id) }}" class="btn btn-outline-primary btn-sm rounded-pill ms-auto">
            <i class="mdi mdi-drag me-1"></i> Open Builder
        </a>
    </div>
    @endif

    {{-- Layout List --}}
    @forelse($layouts as $layout)
    @php $isActive = $layout->is_active; @endphp
    <div class="layout-card {{ $isActive ? 'active-layout' : '' }}">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-1" style="color:#0f172a;">{{ $layout->name }}</h5>
                    @if($isActive)
                    <span class="badge bg-primary rounded-pill px-3" style="font-size:10px;font-weight:700;">Active</span>
                    @endif
                    @if($layout->is_default)
                    <span class="badge bg-secondary rounded-pill px-3" style="font-size:10px;font-weight:700;">Default</span>
                    @endif
                </div>
                <div class="text-muted small">
                    <span>{{ $layout->sections_count }} sections</span>
                    <span class="mx-2">·</span>
                    <span>Created {{ $layout->created_at->diffForHumans() }}</span>
                    @if($layout->created_by)
                    <span class="mx-2">·</span>
                    <span>by {{ optional($layout->creator)->name ?? 'Admin' }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                @if(!$isActive)
                <a href="{{ route('layouts.apply', $layout->id) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick="return confirm('Apply &quot;{{ $layout->name }}&quot; layout?')">
                    <i class="fe-check me-1"></i> Apply
                </a>
                @endif
                <a href="{{ route('layouts.builder', $layout->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="mdi mdi-drag me-1"></i> Builder
                </a>
                <a href="{{ route('layouts.edit', $layout->id) }}" class="btn btn-sm btn-light rounded-pill px-3">
                    <i class="fe-edit-2 me-1"></i> Edit
                </a>
                @if(!$isActive)
                <form action="{{ route('layouts.destroy') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="hidden_id" value="{{ $layout->id }}">
                    <button type="submit" class="btn btn-sm btn-light rounded-pill px-3" style="color:#ef4444;" onclick="return confirm('Delete &quot;{{ $layout->name }}&quot;?')">
                        <i class="fe-trash-2 me-1"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="mdi mdi-view-dashboard-outline" style="font-size:48px;color:#cbd5e1;"></i>
        <h5 class="mt-3 text-muted">No layouts yet</h5>
        <p class="text-muted small">Create a layout to start organizing homepage sections</p>
        <a href="{{ route('layouts.create') }}" class="btn btn-primary rounded-pill mt-2">Create Your First Layout</a>
    </div>
    @endforelse
</div>
@endsection
