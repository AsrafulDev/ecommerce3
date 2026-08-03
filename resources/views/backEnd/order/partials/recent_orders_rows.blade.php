@forelse($orders as $order)
<tr>
    <td><code>{{ $order->invoice_id }}</code></td>
    <td>{{ $order->shipping->name ?? $order->customer->name ?? 'N/A' }}</td>
    <td>{{ $order->orderdetails->sum('qty') }} items</td>
    <td>৳{{ number_format($order->amount, 2) }}</td>
    <td>
        @if($order->due_amount > 0)
            <span class="badge bg-warning">Due ৳{{ number_format($order->due_amount, 2) }}</span>
        @elseif($order->payment_status === 'paid')
            <span class="badge bg-success">Paid</span>
        @else
            <span class="badge bg-secondary">{{ ucfirst($order->payment_status) }}</span>
        @endif
    </td>
    <td>
        @php $stEnum = \App\Enums\OrderStatus::tryFrom($order->order_status); @endphp
        <span class="badge bg-{{ $stEnum ? $stEnum->badgeClass() : 'secondary' }}">
            {{ $stEnum ? $stEnum->label() : $order->order_status }}
        </span>
    </td>
    <td><small>{{ $order->created_at->format('d M, h:i A') }}</small></td>
    <td>
        <div class="d-flex gap-1 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-success recent-print-pos" data-invoice="{{ $order->invoice_id }}" title="Print POS">
                <i class="fa fa-print"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary recent-print-a4" data-invoice="{{ $order->invoice_id }}" title="Print A4">
                <i class="fa fa-print"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning recent-edit" data-invoice="{{ $order->invoice_id }}" title="Edit / Update">
                <i class="fa fa-edit"></i>
            </button>
            @if($order->due_amount > 0)
            <button type="button" class="btn btn-sm btn-outline-info recent-collect" data-id="{{ $order->id }}" data-invoice="{{ $order->invoice_id }}" data-due="{{ $order->due_amount }}" title="Collect Due">
                <i class="fa fa-money-bill-wave"></i>
            </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8" class="text-center text-muted py-4">No orders found.</td>
</tr>
@endforelse
