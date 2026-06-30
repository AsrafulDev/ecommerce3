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
    .preset-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.25s ease;
    }
    .preset-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 8px 30px rgba(59,130,246,0.12);
        transform: translateY(-2px);
    }
    .preset-screenshot {
        width: 100%;
        height: 200px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #94a3b8;
        overflow: hidden;
    }
    .preset-screenshot img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .reset-card {
        background: #fffbfb;
        border: 2px dashed #fca5a5;
        border-radius: 16px;
        padding: 24px;
    }
    .reset-card:hover {
        border-color: #ef4444;
        background: #fef2f2;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="mdi mdi-backup-restore me-2"></i> {{ __('Demo Management') }} </h4>
            <p class="text-muted small m-0 mt-1"> {{ __('Export your current settings or import a complete demo shop with one click') }} </p>
        </div>
    </div>

    {{-- Current Status --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#e0f2fe;color:#0284c7;">
                    <i class="mdi mdi-palette"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $themes->count() }}</h5>
                <small class="text-muted"> {{ __('Themes') }} </small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#dcfce7;color:#16a34a;">
                    <i class="mdi mdi-view-dashboard"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $layouts->count() }}</h5>
                <small class="text-muted"> {{ __('Layouts') }} </small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#fef3c7;color:#d97706;">
                    <i class="mdi mdi-export"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ count($presets) }}</h5>
                <small class="text-muted"> {{ __('Demo Presets') }} </small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="demo-card text-center">
                <div class="demo-icon mx-auto mb-2" style="background:#f3e8ff;color:#9333ea;">
                    <i class="mdi mdi-store"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ count($shopPresets) }}</h5>
                <small class="text-muted"> {{ __('Shop Presets') }} </small>
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
                <small class="text-muted"> {{ __('Active Layout') }} </small>
            </div>
        </div>
    </div>

    {{-- Shop Presets (One-click Import) --}}
    <div class="card shadow-none border rounded-4 mb-4">
        <div class="card-header bg-transparent border-bottom-0 pt-3">
            <h5 class="fw-bold m-0"><i class="mdi mdi-store me-1"></i> {{ __('Available Shop Presets') }} </h5>
            <small class="text-muted"> {{ __('Click any preset to instantly import a complete shop with categories, products, and settings') }} </small>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach($shopPresets as $slug => $preset)
                <div class="col-md-4 col-lg-3">
                    <div class="preset-card">
                        <div class="preset-screenshot" style="background: {{ $preset['color'] }}20; color: {{ $preset['color'] }};">
                            <i class="mdi {{ $preset['icon'] }}"></i>
                        </div>
                        <div class="p-3">
                            <h6 class="fw-bold mb-1">{{ $preset['name'] }}</h6>
                            <p class="small text-muted mb-3">{{ $preset['description'] }}</p>
                            <div class="d-flex gap-2">
                                <form action="{{ route('demo.import-preset', $slug) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill"
                                            onclick="return confirm('Import 「{{ $preset['name'] }}」? This will replace all existing data.')">
                                        <i class="mdi mdi-upload me-1"></i>{{ __('Import') }}</button>
                                </form>
                                <a href="{{ $preset['live_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">
                                    <i class="mdi mdi-open-in-new me-1"></i>{{ __('Preview') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Danger Zone: Full Site Reset --}}
    <div class="reset-card mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold text-danger m-0"><i class="mdi mdi-alert-circle me-1"></i> {{ __('Danger Zone') }} </h5>
                <p class="small text-muted m-0 mt-1"> {{ __('Reset deletes everything then re-seeds default data. Clean wipes everything leaving an empty site.') }} </p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('demo.reset') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3"
                            onclick="return confirm('⚠️ ARE YOU SURE?\n\nThis will PERMANENTLY DELETE all products, categories, orders, and all other data, then re-seed with default demo data.\n\nOnly admin users will be preserved.')">
                        <i class="mdi mdi-restart me-1"></i> Reset + Seed
                    </button>
                </form>
                <form action="{{ route('demo.clean') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger rounded-pill px-3"
                            onclick="return confirm('⚠️⚠️⚠️ ARE YOU SURE?\n\nThis will PERMANENTLY DELETE EVERYTHING — products, categories, orders, all data — with NOTHING added back!\n\nThe site will be completely empty.\n\nOnly admin users will remain.')">
                        <i class="mdi mdi-delete-forever me-1"></i> Clean Everything
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Export / Import --}}
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
                            <h5 class="fw-bold m-0"> {{ __('Export Current Setup') }} </h5>
                            <small class="text-muted"> {{ __('Download your themes, layouts & settings as a zip') }} </small>
                        </div>
                    </div>
                    <p class="small text-muted">This will export all {{ $themes->count() }} themes, {{ $layouts->count() }} layouts, and section configurations.</p>
                    <a href="{{ route('demo.export') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="mdi mdi-download me-1"></i> Export Demo
                    </a>
                </div>
            </div>
        </div>

        {{-- Import Preset Zip Card --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-none border rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="demo-icon me-3" style="background:#dcfce7;color:#16a34a;">
                            <i class="mdi mdi-package-variant-closed"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0"> {{ __('Upload Preset Zip') }} </h5>
                            <small class="text-muted">Upload a preset zip (data.json + images/)</small>
                        </div>
                    </div>
                    <form action="{{ route('demo.import-zip') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="preset_zip" class="form-control" accept=".zip" required>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill px-4">
                            <i class="mdi mdi-upload me-1"></i> Import Preset Zip
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
            <h5 class="fw-bold m-0"><i class="mdi mdi-package-variant-closed me-1"></i> {{ __('Saved Demo Presets') }} </h5>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Action') }}</th>
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
                                        <i class="mdi mdi-upload me-1"></i>{{ __('Import') }}</button>
                                </form>
                                <a href="{{ route('demo.delete-preset', basename($preset['name'])) }}" 
                                   class="btn btn-sm btn-outline-danger rounded-pill"
                                   onclick="return confirm('Delete this preset?')">
                                    <i class="mdi mdi-delete me-1"></i>{{ __('Delete') }}</a>
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
