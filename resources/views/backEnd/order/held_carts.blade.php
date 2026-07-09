@extends('backEnd.layouts.master')
@section('title', 'Held Carts')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; color: #5a5c69; }
    .badge-held { font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 4px; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="pause-circle" class="text-warning me-1"></i> Held Carts
        </h4>
        <a href="{{ route('admin.order.create') }}" class="btn btn-danger rounded-pill shadow-sm">
            <i data-feather="cpu" class="me-1" style="width:14px;"></i> POS
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Items</th>
                            <th>Subtotal</th>
                            <th>Grand Total</th>
                            <th>Note</th>
                            <th>Held At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($heldCarts as $cart)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $cart->customer_name ?: 'Walk-in' }}</td>
                                <td>{{ $cart->customer_phone ?: '—' }}</td>
                                <td>
                                    @php $items = is_array($cart->cart_data) ? count($cart->cart_data) : 0; @endphp
                                    {{ $items }}
                                </td>
                                <td>৳{{ number_format($cart->subtotal, 2) }}</td>
                                <td class="fw-bold">৳{{ number_format($cart->grand_total, 2) }}</td>
                                <td><small class="text-muted">{{ Str::limit($cart->note, 20) ?: '—' }}</small></td>
                                <td><small>{{ $cart->held_at ? date('d/m/Y h:i A', strtotime($cart->held_at)) : '—' }}</small></td>
                                <td>
                                    @php $b = match($cart->status) { 'held' => 'warning', 'restored' => 'info', 'converted' => 'success', 'cancelled' => 'danger', default => 'secondary' }; @endphp
                                    <span class="badge bg-{{ $b }} badge-held">{{ ucfirst($cart->status) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($cart->status === 'held')
                                            <form action="{{ route('admin.order.restore_hold', $cart->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill" onclick="return confirm('Restore this cart? Current cart will be cleared.')">
                                                    <i class="fe-refresh-ccw"></i> Restore
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.order.delete_hold', $cart->id) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Delete this held cart?')">
                                                    <i class="fe-trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">No held carts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
