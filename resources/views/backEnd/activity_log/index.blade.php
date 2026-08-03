@extends('backEnd.layouts.master')
@section('title', 'Activity Logs')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>🛡️ {{ __('Activity Logs') }} <small class="text-muted fs-6">({{ __('security audit') }})</small></h4>
    </div>

    {{-- Filter bar --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">{{ __('Month') }}</label>
                    <select name="month" class="form-select">
                        @foreach($months as $ym)
                            <option value="{{ $ym }}" {{ $month === $ym ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</option>
                        @endforeach
                        <option value="{{ now()->format('Y-m') }}" {{ $month === now()->format('Y-m') ? 'selected' : '' }}>{{ __('Current') }}</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">{{ __('User') }}</label>
                    <select name="user" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string) $userFilter === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small text-muted mb-1">{{ __('Module') }}</label>
                    <select name="module" class="form-select">
                        <option value="">All Modules</option>
                        @foreach($modules as $m)
                            <option value="{{ $m }}" {{ $moduleFilter === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">{{ __('Search') }}</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search description...">
                </div>
                <div class="col-md-1 col-12 d-flex gap-1">
                    <button class="btn btn-primary w-100">Filter</button>
                    @if($search !== '' || $userFilter || $moduleFilter)
                        <a href="{{ route('admin.activity_logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date / Time</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                            <td class="text-nowrap">
                                <span class="small">{{ $log->created_at?->format('d M, Y') }}</span>
                                <div class="text-muted small">{{ $log->created_at?->format('h:i A') }}</div>
                            </td>
                            <td>
                                <strong>{{ $log->user_name ?? ($log->user->name ?? 'System') }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ ucfirst($log->module) }}</span>
                            </td>
                            <td>
                                @php
                                    $actionColor = match($log->action) {
                                        'create' => 'success',
                                        'delete' => 'danger',
                                        'update' => 'info',
                                        'void'   => 'warning',
                                        default  => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $actionColor }}">{{ $log->action }}</span>
                            </td>
                            <td>
                                {{ $log->description }}
                                @if($log->data)
                                    <button class="btn btn-xs btn-link p-0 ms-1 d-none" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#logData{{ $log->id }}">
                                        <i class="fa fa-chevron-down"></i>
                                    </button>
                                    <div class="collapse mt-1" id="logData{{ $log->id }}">
                                        <pre class="small bg-light p-2 rounded mb-0" style="max-height:160px;overflow:auto;">{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif
                            </td>
                            <td><code class="small">{{ $log->ip ?? '—' }}</code></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No activity logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
