@extends('backEnd.layouts.master')
@section('title', $config['label'].' List')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Finance Setup</h3>
            <p class="text-muted mb-0">Lists used by the finance pages. Changing a name here does not change any figure already booked.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounts.reports.index') }}">Finance reports</a>
    </div>

    <ul class="nav nav-pills mb-3 gap-1">
        @foreach ($types as $slug => $item)
            <li class="nav-item">
                <a class="nav-link {{ $slug === $type ? 'active' : '' }}" href="{{ route('admin.finance.masters.index', $slug) }}">{{ $item['label'] }}</a>
            </li>
        @endforeach
    </ul>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Add {{ $config['label'] }}</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.finance.masters.store', $type) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label small mb-1">{{ $config['column'] === 'title' ? 'Title' : 'Name' }} *</label>
                    <input type="text" name="{{ $config['column'] }}" value="{{ old($config['column']) }}" class="form-control form-control-sm" required maxlength="150">
                </div>
                @if ($type === 'account-group')
                <div class="col-md-4">
                    <label class="form-label small mb-1">Account Category *</label>
                    <select name="account_category_id" class="form-select form-select-sm" required>
                        <option value="">Select…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('account_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Entry</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong>{{ $config['label'] }} List</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">SL</th>
                        <th>{{ $config['column'] === 'title' ? 'Title' : 'Name' }}</th>
                        @if ($type === 'account-group')<th>Category</th>@endif
                        <th class="text-end" style="width:260px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row->{$config['column']} }}</td>
                            @if ($type === 'account-group')<td>{{ $row->category_name }}</td>@endif
                            <td class="text-end">
                                <details class="d-inline-block text-start">
                                    <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                    <form method="POST" action="{{ route('admin.finance.masters.update', [$type, $row->id]) }}" class="d-flex flex-wrap gap-1 mt-2">
                                        @csrf
                                        <input type="text" name="{{ $config['column'] }}" value="{{ $row->{$config['column']} }}" class="form-control form-control-sm" required maxlength="150">
                                        @if ($type === 'account-group')
                                        <select name="account_category_id" class="form-select form-select-sm" required>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected($row->account_category_id == $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @endif
                                        <button class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('admin.finance.masters.destroy', [$type, $row->id]) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this {{ strtolower($config['label']) }}?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Nothing added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
