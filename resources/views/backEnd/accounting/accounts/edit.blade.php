@extends('backEnd.layouts.master')

@section('title', $account->code . ' — ' . $account->name)

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Edit account') }} <span class="text-muted">{{ $account->code }}</span></h3>
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-sm btn-light">{{ __('Back to chart') }}</a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.accounting.accounts.update', $account->id) }}">
                        @csrf
                        @method('POST')

                        <div class="mb-3">
                            <label class="form-label">{{ __('Account code') }}</label>
                            <input type="text" class="form-control" value="{{ $account->code }}" disabled>
                            <div class="form-text">{{ __('Codes are part of the chart setup and are not changed from here.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" required maxlength="150"
                                   class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}">
                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" rows="3" maxlength="500"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $account->description) }}</textarea>
                            @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   id="is_active" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('Active — available for posting') }}</label>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('admin.accounting.ledger.account', $account->id) }}" class="btn btn-sm btn-link">{{ __('Open ledger') }}</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-light">{{ __('Locked, by design') }}</div>
                <div class="card-body">
                    <table class="table table-sm mb-2">
                        <tr>
                            <th class="text-muted small">{{ __('Class') }}</th>
                            <td>{{ $account->account_type->label() }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Normal balance') }}</th>
                            <td>{{ ucfirst($account->normal_balance->value) }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Posting role') }}</th>
                            <td><code>{{ $account->role ?: '—' }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Balance') }}</th>
                            <td class="fw-bold">{{ Money::format($account->balance()) }}</td>
                        </tr>
                    </table>
                    <p class="text-muted small mb-0">
                        {{ __('Class, normal balance and role decide where money posts. Changing them from a dashboard would silently move future journals, so they are setup-only and cannot be edited here.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
