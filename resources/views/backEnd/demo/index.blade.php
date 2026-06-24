@extends('backEnd.layouts.master')
@section('title', 'Demo Management')

@section('css')
<style>
    .demo-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        transition: all 0.2s;
    }
    .demo-card:hover { border-color: #3b82f6; box-shadow: 0 4px 20px rgba(59,130,246,0.08); }
    .demo-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="mdi mdi-backup-restore me-2"></i> Demo Management</h4>
            <p class="text-muted small m-0 mt-1">Export your current theme/layout settings or import demo presets</p>
        </div>
    </div>

    {{-- Current Status --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#e0f2fe;color:#0284c7;">
                    <i class="mdi mdi-palette"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $themes->count() }}</h5>
                <small class="text-muted">Themes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#dcfce7;color:#16a34a;">
                    <i class="mdi mdi-view-dashboard"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $layouts->count() }}</h5>
                <small class="text-muted">Layouts</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#fef3c7;color:#d97706;">
                    <i class="mdi mdi-export"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ count($presets) }}</h5>
                <small class="text-muted">Demo Presets</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#f3e8ff;color:#9333ea;">
                    <i class="mdi mdi-download"></i>
                </div>
                <h5 class="fw-bold mb-0">
                    @if($activeLayout) {{ $activeLayout->name }} @else N/A @endif
                </h5>
                <small class="text-muted">Active Layout</small>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Export Card --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-none border rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="demo-icon me-3" style="background:#e0f2fe;color:#0284c7;">
                            <i class="mdi mdi-export"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0">Export Current Setup</h5>
                            <small class="text-muted">Download your themes, layouts & settings as a zip</small>
                        </div>
                    </div>
                    <p class="small text-muted">This will export all {{ $themes->count() }} themes, {{ $layouts->count() }} layouts, and section configurations.</p>
                    <a href="{{ route('demo.export') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="mdi mdi-download me-1"></i> Export Demo
                    </a>
                </div>
            </div>
        </div>

        {{-- Import Card --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-none border rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="demo-icon me-3" style="background:#dcfce7;color:#16a34a;">
                            <i class="mdi mdi-upload"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0">Import Demo</h5>
                            <small class="text-muted">Upload a demo zip file</small>
                        </div>
                    </div>
                    <form action="{{ route('demo.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="demo_file" class="form-control" accept=".zip" required>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill px-4">
                            <i class="mdi mdi-upload me-1"></i> Import Demo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Available Presets --}}
    @if(count($presets) > 0)
    <div class="card shadow-none border rounded-4">
        <div class="card-header bg-transparent border-bottom-0 pt-3">
            <h5 class="fw-bold m-0"><i class="mdi mdi-package-variant-closed me-1"></i> Saved Demo Presets</h5>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($presets as $preset)
                        <tr>
                            <td><i class="mdi mdi-zip-box text-primary me-1"></i> {{ $preset['name'] }}</td>
                            <td>{{ round($preset['size'] / 1024, 1) }} KB</td>
                            <td>{{ date('d M Y H:i', $preset['modified']) }}</td>
                            <td>
                                <form action="{{ route('demo.import') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="demo_file" value="preset">
                                    <input type="hidden" name="preset_path" value="{{ $preset['path'] }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">
                                        <i class="mdi mdi-upload me-1"></i> Import
                                    </button>
                                </form>
                                <a href="{{ route('demo.delete-preset', basename($preset['name'])) }}" 
                                   class="btn btn-sm btn-outline-danger rounded-pill"
                                   onclick="return confirm('Delete this preset?')">
                                    <i class="mdi mdi-delete me-1"></i> Delete
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
