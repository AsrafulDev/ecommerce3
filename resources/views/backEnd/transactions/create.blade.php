@extends('backEnd.layouts.master')
@section('title', 'New Transaction')
@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header"><h4 class="mb-0">New Transaction</h4><small>Category determines the accounting treatment.</small></div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.transactions.store') }}" class="row g-3">
        @csrf
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label">Amount</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount') }}" required></div>
        <div class="col-md-6"><label class="form-label">Transaction category</label><select name="category" class="form-control" required><option value="">Select category</option><optgroup label="Fund / Equity"><option value="owner_capital">Owner capital</option><option value="other_income">Other income</option><option value="owner_withdrawal">Owner withdrawal</option></optgroup><optgroup label="Expenses">@foreach($expenseCategories as $category)<option value="{{ $category }}">{{ ucwords(str_replace('_', ' ', $category)) }}</option>@endforeach</optgroup></select></div>
        <div class="col-md-4"><label class="form-label">Transaction by</label><select name="person_id" class="form-control" @disabled(!$isAdmin)>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)old('person_id', auth('admin')->id()) === (int)$user->id)>{{ $user->name }}{{ $user->email ? ' — '.$user->email : '' }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Related type</label><input name="related_type" class="form-control" value="{{ old('related_type') }}" placeholder="e.g. salary, purchase"></div>
        <div class="col-md-4"><label class="form-label">Related record ID</label><input type="number" name="related_id" class="form-control" value="{{ old('related_id') }}" placeholder="Optional source ID"></div>
        <div class="col-12"><label class="form-label">Extra details</label><textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Save transaction</button></div>
      </form>
    </div>
  </div>
</div>
@endsection
