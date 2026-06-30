@extends('backEnd.layouts.master')
@section('title', 'Reseller Deposits')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="wallet" class="text-primary me-2"></i> {{ __('bn_ce1342da') }} ডিপোজিট
            </h4>
            <p class="text-muted small mb-0">{{ __('bn_3f4b9b8c') }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.reseller-deposits.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    সব
                </a>
                <a href="{{ route('admin.reseller-deposits.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                    {{ __('Pending') }}
                </a>
                <a href="{{ route('admin.reseller-deposits.index', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-success' : 'btn-outline-success' }}">
                    {{ __('bn_623a38a3') }}
                </a>
                <a href="{{ route('admin.reseller-deposits.index', ['status' => 'failed']) }}" class="btn btn-sm {{ request('status') === 'failed' ? 'btn-danger' : 'btn-outline-danger' }}">
                    {{ __('bn_84d84036') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('bn_ce1342da') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('bn_65934baf') }}</th>
                        <th class="text-end">{{ __('bn_7c6fd8c1') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td>
                            <div>
                                <strong>{{ $d->user->name ?? '{{ __('N/A') }}' }}</strong>
                                @if($d->user->shop_name)
                                <br><small class="text-muted">{{ $d->user->shop_name }}</small>
                                @endif
                                <br><small class="text-muted">{{ $d->user->email ?? '' }}</small>
                            </div>
                        </td>
                        <td><strong class="text-success">৳{{ number_format($d->amount, 2) }}</strong></td>
                        <td>
                            @if($d->status === 'pending')
                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                            @elseif($d->status === 'completed')
                            <span class="badge bg-success">{{ __('bn_623a38a3') }}</span>
                            @else
                            <span class="badge bg-danger">{{ __('bn_84d84036') }}</span>
                            @endif
                        </td>
                        <td>{{ $d->created_at->format('d M Y, h:i A') }}</td>
                        <td><small class="text-muted">{{ $d->transaction_id ?? '-' }}</small></td>
                        <td class="text-end">
                            @if($d->status === 'pending')
                            <form action="{{ route('admin.reseller-deposits.mark-paid', $d->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} class="d-inline" on{{ __('submit') }}="return confirm('{{ __('bn_f0a1817c') }} নিশ্চিত করেছেন? ওয়ালেটে টাকা যোগ হবে।');">
                                @csrf
                                <button type="{{ __('submit') }}" class="btn btn-sm btn-success">
                                    <i class="mdi mdi-check-circle"></i> {{ __('bn_623a38a3') }} মার্ক করুন
                                </button>
                            </form>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">{{ __('bn_356b6cc7') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deposits->hasPages())
        <div class="card-footer">
            {{ $deposits->withQuery{{ __('String') }}()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
