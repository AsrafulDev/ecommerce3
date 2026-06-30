@extends('backEnd.layouts.master')
@section('title', 'Backup & Restore')

@push('css')
<style>
    .backup-card { border-radius: 12px; border: 1px solid #e5e7eb; transition: all 0.3s; }
    .backup-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .backup-icon { font-size: 32px; }
    .file-drop-area {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    .file-drop-area:hover { border-color: #6366f1; background: #f8f7ff; }
    .preset-item { border-radius: 8px; padding: 12px; border: 1px solid #eee; transition: all 0.2s; }
    .preset-item:hover { border-color: #6366f1; background: #fafafe; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-sm-12">
            <h4 class="page-title"><i class="mdi mdi-backup-restore text-primary"></i>{{ __('Backup & Restore') }}</h4>
        </div>
    </div>

    {{-- ======== ROW 1: FULL SITE BACKUP ======== --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card backup-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="backup-icon me-3">💾</span>
                        <div>
                            <h5 class="mb-0"> {{ __('Full Site Backup') }} </h5>
                            <small class="text-muted"> {{ __('Database + Uploads + Settings') }} </small>
                        </div>
                    </div>
                    <p class="text-muted small"> {{ __('Creates a complete ZIP backup of your entire site including all database tables, uploaded files, and settings.') }} </p>
                    <form action="{{ route('backup.create') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-plus-circle"></i> Create Backup Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card backup-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="backup-icon me-3">📥</span>
                        <div>
                            <h5 class="mb-0"> {{ __('Restore Backup') }} </h5>
                            <small class="text-muted"> {{ __('Upload a backup ZIP to restore') }} </small>
                        </div>
                    </div>
                    <p class="text-muted small">Upload a previously created backup ZIP file. ⚠️ This will replace ALL current data.</p>
                    <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="file-drop-area mb-3" onclick="document.getElementById('backup_file').click()">
                            <i class="mdi mdi-cloud-upload fs-1 text-muted"></i>
                            <p class="mb-0 mt-2"> {{ __('Click to select backup ZIP') }} </p>
                            <input type="file" name="backup_file" id="backup_file" class="d-none" accept=".zip" required onchange="this.parentNode.querySelector('p').textContent = this.files[0].name">
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('⚠️ This will replace ALL current data. Continue?')">
                            <i class="mdi mdi-restore"></i> Restore from Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ======== ROW 2: THEME & LAYOUT IMPORT/EXPORT ======== --}}
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card backup-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="backup-icon me-3">🎨</span>
                        <div>
                            <h5 class="mb-0"> {{ __('Theme Export / Import') }} </h5>
                            <small class="text-muted"> {{ __('Colors, logos, typography settings') }} </small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('theme.export') }}" class="btn btn-outline-primary flex-fill">
                            <i class="mdi mdi-download"></i> Export Theme
                        </a>
                        <button class="btn btn-outline-secondary flex-fill" onclick="document.getElementById('theme_file').click()">
                            <i class="mdi mdi-upload"></i> Import Theme
                        </button>
                    </div>
                    <form action="{{ route('theme.import') }}" method="POST" enctype="multipart/form-data" class="mt-2">
                        @csrf
                        <input type="file" name="theme_file" id="theme_file" class="d-none" accept=".json" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card backup-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="backup-icon me-3">📐</span>
                        <div>
                            <h5 class="mb-0"> {{ __('Layout Export / Import') }} </h5>
                            <small class="text-muted"> {{ __('Homepage layout sections & order') }} </small>
                        </div>
                    </div>
                    <form action="{{ route('layout.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <select name="layout_id" class="form-select form-select-sm mb-2" id="export-layout-select">
                                <option value="">-- Export a layout --</option>
                                @foreach($layouts as $layout)
                                    <option value="{{ $layout->id }}">{{ $layout->name }} ({{ $layout->sections_count }} sections)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary flex-fill" onclick="exportLayout()">
                                <i class="mdi mdi-download"></i> Export Layout
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="document.getElementById('layout_file').click()">
                                <i class="mdi mdi-upload"></i> Import Layout
                            </button>
                        </div>
                        <input type="file" name="layout_file" id="layout_file" class="d-none" accept=".json" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ======== ROW 3: PRESET DOWNLOADS ======== --}}
    <div class="row mt-4">
        <div class="col-sm-12">
            <div class="card backup-card">
                <div class="card-body">
                    <h5 class="mb-3">📦 Demo Presets — Download / Restore</h5>
                    <div class="row g-3">
                        @foreach($presets as $slug => $meta)
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                            <div class="preset-item text-center">
                                <span class="d-block" style="font-size:24px;color:{{ $meta['color'] ?? '#6366f1' }};">
                                    <i class="mdi {{ $meta['icon'] ?? 'mdi-package-variant' }}"></i>
                                </span>
                                <strong class="d-block small mt-1">{{ $meta['name'] }}</strong>
                                <div class="d-grid gap-1 mt-2">
                                    <a href="{{ route('preset.download', $slug) }}" class="btn btn-sm btn-outline-primary w-100" title="Download preset ZIP">
                                        <i class="mdi mdi-download"></i>{{ __('Download') }}</a>
                                    <a href="{{ route('preset.restore-theme', $slug) }}" class="btn btn-sm btn-outline-warning w-100" title="Apply colors & logos">
                                        <i class="mdi mdi-palette"></i> {{ __('Theme') }} </a>
                                    <a href="{{ route('preset.restore-layout', $slug) }}" class="btn btn-sm btn-outline-success w-100" title="Create layout from preset">
                                        <i class="mdi mdi-view-dashboard"></i> Layout
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======== ROW 4: EXISTING BACKUPS ======== --}}
    @if(count($backups) > 0)
    <div class="row mt-4">
        <div class="col-sm-12">
            <div class="card backup-card">
                <div class="card-body">
                    <h5 class="mb-3">📁 Existing Backups</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th> {{ __('Filename') }} </th>
                                    <th>{{ __('Size') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end"> {{ __('Actions') }} </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                <tr>
                                    <td><code>{{ $backup['filename'] }}</code></td>
                                    <td>{{ $backup['size'] }}</td>
                                    <td>{{ $backup['date'] }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('backup.download') }}?file={{ $backup['filename'] }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                        <a href="{{ route('backup.delete', basename($backup['filename'])) }}" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this backup?')">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('script')
<script>
    function exportLayout() {
        var layoutId = document.getElementById('export-layout-select').value;
        if (!layoutId) { alert('Please select a layout to export'); return; }
        // Create a hidden form and submit it
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("layout.export") }}';
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'layout_id';
        input.value = layoutId;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
@endsection
