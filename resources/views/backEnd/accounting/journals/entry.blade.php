@extends('backEnd.layouts.master')
@section('title', 'Journal Entry')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Journal Entry</h3>
            <p class="text-muted mb-0">A manual journal. Debits must equal credits. Posting is final: correct a mistake with a reversal.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.journals.index') }}">All journals</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.accounting.journals.entry.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Date *</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label small mb-1">Description *</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="255" class="form-control form-control-sm" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account *</th>
                            <th>Line note</th>
                            <th class="text-end" style="width:140px;">Debit</th>
                            <th class="text-end" style="width:140px;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 6; $i++)
                        <tr>
                            <td>
                                <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected(old("lines.$i.account_id") == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="lines[{{ $i }}][description]" value="{{ old("lines.$i.description") }}" maxlength="255" class="form-control form-control-sm"></td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" value="{{ old("lines.$i.debit") }}" class="form-control form-control-sm text-end"></td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" value="{{ old("lines.$i.credit") }}" class="form-control form-control-sm text-end"></td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="small text-muted">Blank lines are ignored. A line moves one way only.</span>
            <button class="btn btn-primary btn-sm" onclick="return confirm('Post this journal? It cannot be edited afterwards.');">Post journal</button>
        </div>
    </form>
</div>
@endsection
