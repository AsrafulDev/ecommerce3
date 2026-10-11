@extends('backEnd.layouts.master')
@section('title', 'New Transaction')
@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h3 class="mb-1">Record a transaction</h3><p class="text-muted mb-0">Enter owner funds, other income, withdrawals, or a business expense.</p></div><a href="{{ route('admin.accounts.dashboard') }}" class="btn btn-outline-secondary">Transactions overview</a></div>
  <div class="alert alert-info"><strong>Choose the right category.</strong> Owner capital increases available funds but is not income. Use an expense category for operating costs so reports classify it correctly.</div>
  <div class="card">
    <div class="card-header bg-white"><h5 class="mb-0">Transaction details</h5></div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.transactions.store') }}" class="row g-3">
        @csrf
        <div class="col-md-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label">Amount (৳) <span class="text-danger">*</span></label><input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount') }}" placeholder="0.00" required></div>
        <div class="col-md-6"><label class="form-label">Transaction category <span class="text-danger">*</span></label><select name="category" class="form-control" required><option value="">Select category</option><optgroup label="Money in / owner funds"><option value="owner_capital">Owner capital</option><option value="other_income">Other income</option><option value="owner_withdrawal">Owner withdrawal</option></optgroup><optgroup label="Business expenses">@foreach($expenseCategories as $category)<option value="{{ $category }}">{{ ucwords(str_replace('_', ' ', $category)) }}</option>@endforeach</optgroup></select></div>
        <div class="col-md-4"><label class="form-label">Transaction by</label><select name="person_id" class="form-control" @disabled(!$isAdmin)>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)old('person_id', auth('admin')->id()) === (int)$user->id)>{{ $user->name }}{{ $user->email ? ' — '.$user->email : '' }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Related record type <span class="text-muted">(optional)</span></label><input name="related_type" class="form-control" value="{{ old('related_type') }}" placeholder="For example: salary or purchase"></div>
        <div class="col-md-4"><label class="form-label">Related record ID <span class="text-muted">(optional)</span></label><input type="number" name="related_id" class="form-control" value="{{ old('related_id') }}" placeholder="Source record number"></div>
        <div class="col-12"><label class="form-label">Note <span class="text-muted">(optional)</span></label><textarea name="note" class="form-control" rows="3" placeholder="Add a short explanation or payment reference">{{ old('note') }}</textarea></div>
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2"><small class="text-muted">The selected category controls how this entry appears in transaction summaries.</small><button class="btn btn-primary px-4">Save transaction</button></div>
      </form>
    </div>
  </div>
</div>
@endsection
