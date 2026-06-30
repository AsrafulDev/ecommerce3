@extends('backEnd.layouts.master')
@section('title','{{ __('{{ __('Expense') }}s') }}')

@php
    use Illuminate\Support\Facades\Auth;
    // {{ __('Check') }} if current user is Admin (Super Admin or has Admin role)
    $isAdmin = false;
    $user = Auth::guard('admin')->user();
    if ($user) {
        if ($user->id == 1) {
            $isAdmin = true;
        } else {
            $spatie{{ __('Roles') }} = $user->getRole{{ __('Name') }}s()->map(function($role) {
                return strtolower($role);
            })->toArray();
            $isAdmin = in_array('admin', $spatie{{ __('Roles') }});
        }
    }
@endphp

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">{{ __('bn_7004dd05') }}</h4>
    </div>

    {{-- ======= SUMMARY CARDS ======= --}}
    <div class="row mb-4">

      {{-- {{ __('Available {{ __('Balance') }}') }} --}}
<div class="col-md-3 mb-3">
    <div class="card bg-success text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">{{ __('Available {{ __('Balance') }}') }}</h5>
            <h2 class="mb-0" style="color:#fff !important;">{{ number_format($balance, 2) }} ৳</h2>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                বর্তমানে তহবিলে অবশিষ্ট ব্যালেন্স
            </small>
        </div>
    </div>
</div>

{{-- This Year {{ __('Expense') }} --}}
<div class="col-md-3 mb-3">
    <div class="card bg-primary text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">This Year ({{ $currentYear }})</h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($yearly{{ __('Expense') }}, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই বছরে {{ __('bn_70ac0f2d') }} খরচ {{ __('bn_290a7f61') }}েছে
            </small>
        </div>
    </div>
</div>

{{-- This Month {{ __('Expense') }} --}}
<div class="col-md-3 mb-3">
    <div class="card bg-info text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">
                This Month ({{ \Carbon\Carbon::create{{ __('From') }}{{ __('Date') }}(now()->year, $currentMonth, 1)->format('F') }})
            </h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($monthly{{ __('Expense') }}, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                এই মাসে {{ __('bn_70ac0f2d') }} খরচ {{ __('bn_290a7f61') }}েছে
            </small>
        </div>
    </div>
</div>

{{-- {{ __('Today') }} {{ __('Expense') }} --}}
<div class="col-md-3 mb-3">
    <div class="card bg-danger text-white" style="color:#fff !important;">
        <div class="card-body" style="color:#fff !important;">
            <h5 class="mb-1" style="color:#fff !important;">{{ __('Today') }} ({{ now()->format('d M, Y') }})</h5>
            <h3 class="mb-0" style="color:#fff !important;">{{ number_format($today{{ __('Expense') }}, 2) }} ৳</h3>
            <small class="opacity-75 d-block mt-1" style="color:#fff !important;">
                আজকে {{ __('bn_70ac0f2d') }} খরচ {{ __('bn_290a7f61') }}েছে
            </small>
        </div>
    </div>
</div>


    </div>

    {{-- ======= FORM & EXPORT ROW ======= --}}
    <div class="row">

        {{-- Add {{ __('Expense') }} --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>{{ __('+ Add {{ __('Expense') }}') }}</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.expenses.store') }}" method={{ __('"{{ __('POST') }}"') }}>
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">{{ __('{{ __('Title') }} *') }}</label>
                            <input type="text"
                                   name="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('bn_b4e82b3d') }}</label>
                            <input type="{{ __('number') }}"
                                   step="0.01"
                                   name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}"
                                   required>
                            @error('amount')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('{{ __('Date') }} *') }}</label>
                            <input type="date"
                                   name="expense_date"
                                   class="form-control @error('expense_date') is-invalid @enderror"
                                   value="{{ old('expense_date', now()->format('Y-m-d')) }}"
                                   required>
                            @error('expense_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('{{ __('Category') }} (optional)') }}</label>
                            <input type="text"
                                   name="category"
                                   class="form-control"
                                   value="{{ old('category') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('{{ __('Note') }} (optional)') }}</label>
                            <textarea name="note"
                                      class="form-control"
                                      rows="3">{{ old('note') }}</textarea>
                        </div>

                        <button type="{{ __('submit') }}" class="btn btn-danger">
                            Save {{ __('Expense') }}
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- {{ __('Export Report') }} --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>📤 {{ __('Export Report') }}</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenses.export') }}" method="{{ __('GET') }}" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">{{ __('{{ __('From') }} {{ __('Date') }}') }}</label>
                            <input type="date" name="from_date" class="form-control"
                                   value="{{ request('from_date') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('To {{ __('Date') }}') }}</label>
                            <input type="date" name="to_date" class="form-control"
                                   value="{{ request('to_date') }}">
                        </div>

                        <button type="{{ __('submit') }}" class="btn btn-outline-primary w-100">
                            ⬇ Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- ======= HISTORY TABLE ======= --}}
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>🧾 {{ __('Expense') }} History</strong>
            <div>
                <a href="{{ route('admin.expenses.logs') }}" class="btn btn-sm btn-outline-info">
                    <i data-feather="file-text" class="me-1" style="width:{{ __('14px') }};height:{{ __('14px') }};"></i> View Logs / Reports
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('{{ __('Date') }}') }}</th>
                    <th>{{ __('{{ __('Title') }}') }}</th>
                    <th>{{ __('{{ __('Category') }}') }}</th>
                    <th class="text-end">{{ __('bn_31ee207e') }}</th>
                    <th>{{ __('{{ __('Note') }}') }}</th>
                    @if($isAdmin)
                    <th>{{ __('Actions') }}</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $exp)
                    <tr>
                        <td>{{ $loop->iteration + ($expenses->currentPage() - 1)*$expenses->perPage() }}</td>
                        <td>{{ \Carbon\Carbon::parse($exp->expense_date)->format('d M, Y') }}</td>
                        <td>
                            {{ $exp->title }}
                            @if($exp->updated_by)
                                <span class="badge bg-warning ms-1" title="This expense has been edited">
                                    <i class="fe-edit" style="width:12px;height:12px;"></i> Edited
                                </span>
                            @endif
                        </td>
                        <td>{{ $exp->category ?? '-' }}</td>
                        <td class="text-end">{{ number_format($exp->amount, 2) }}</td>
                        <td>{{ $exp->note }}</td>
                        @if($isAdmin)
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.expenses.edit', $exp->{{ __('id)') }} }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                    <i class="fe-edit"></i>
                                </a>
                                <form method={{ __('"{{ __('POST') }}"') }} action="{{ route('admin.expenses.destroy', $exp->{{ __('id)') }} }}" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="{{ __('submit') }}" class="btn btn-sm btn-outline-danger delete-confirm" title="{{ __('Delete') }}" onclick="return confirm('Are you sure you want to delete this expense?');">
                                        <i class="fe-trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}" class="text-center text-muted">
                            কোনো খরচের রেকর্ড পাওয়া যায়নি।
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{ $expenses->links() }}
        </div>
    </div>

</div>
@endsection
