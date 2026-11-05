@extends('backEnd.layouts.master')
@section('title', 'Sales reset')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Sales reset</h4>
            <small class="text-muted">Cancel a sale (the default): stock, ledger and VAT linked to it are reversed automatically and the sale stays in history as cancelled. Complete delete removes a cancelled sale that has no payment, refund, warranty or declared VAT behind it.</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning btn-sm" data-bulk="cancel">Reverse selected</button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bulk="delete">Complete delete selected</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->has('reset'))
        <div class="alert alert-warning">
            @foreach((array) $errors->get('reset') as $message)
                @foreach((array) $message as $line)<div>{{ $line }}</div>@endforeach
            @endforeach
        </div>
    @endif
    @if($errors->any() && ! $errors->has('reset'))
        <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="pickAll" aria-label="Select all"></th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Buyer</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Reverse</th>
                        <th>Complete delete</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $row)
                        @php($order = $row['order'])
                        <tr>
                            <td><input type="checkbox" class="pick" value="{{ $order->id }}" data-blocked="{{ $row['blockers'] === [] ? 0 : 1 }}"></td>
                            <td>{{ $order->invoice_id ?: '#'.$order->id }}</td>
                            <td>{{ optional($order->created_at)->format('d M Y') }}</td>
                            <td>{{ $row['buyer'] ?: '—' }}</td>
                            <td class="text-end">৳{{ number_format((float) $order->amount, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ $order->order_status }}</span></td>
                            <td>
                                @if($row['reverse']['mode'] === 'cancel')
                                    <span class="badge bg-warning text-dark">Cancel</span>
                                @elseif($row['reverse']['mode'] === 'return')
                                    <span class="badge bg-info text-dark">Return (ends as returned)</span>
                                @else
                                    @foreach($row['reverse']['blockers'] as $blocker)
                                        <div class="small text-danger">{{ $blocker }}</div>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if($row['blockers'] === [])
                                    <span class="badge bg-success">Allowed</span>
                                @else
                                    @foreach($row['blockers'] as $blocker)
                                        <div class="small text-danger">{{ $blocker }}</div>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($row['reverse']['mode'] !== null)
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-single="cancel" data-id="{{ $order->id }}" data-label="{{ $order->invoice_id ?: '#'.$order->id }}">{{ $row['reverse']['mode'] === 'cancel' ? 'Cancel' : 'Return' }}</button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger" data-single="delete" data-id="{{ $order->id }}" data-label="{{ $order->invoice_id ?: '#'.$order->id }}" data-blocked="{{ $row['blockers'] === [] ? 0 : 1 }}" @disabled($row['blockers'] !== [])>Complete delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No sales.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.transaction-reset.act') }}" class="modal-content">
            @csrf
            <input type="hidden" name="action" id="resetAction">
            <div id="resetIds"></div>
            <div class="modal-header">
                <h5 class="modal-title" id="resetTitle">Reset sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Reverse related (automatic)</strong></p>
                <div class="form-check"><input class="form-check-input" type="checkbox" checked disabled> <label class="form-check-label">Stock goes back into inventory</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" checked disabled> <label class="form-check-label">Ledger entries reversed (receivable, revenue and cost)</label></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" checked disabled> <label class="form-check-label">Declared VAT reversed by a credit note</label></div>
                <p class="small text-muted" id="resetDeleteNote" style="display:none;">Complete delete also removes the order, its lines and its delivery details. The reversals above stay in the audit history.</p>

                <label class="form-label">Reason (at least 10 characters)</label>
                <textarea name="reason" class="form-control mb-2" rows="2" minlength="10" required></textarea>

                <div id="resetConfirmWrap" style="display:none;">
                    <label class="form-label">Type DELETE to confirm</label>
                    <input name="confirmation" class="form-control mb-2" autocomplete="off">
                </div>

                <label class="form-label">Your admin password</label>
                <input type="password" name="password" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-danger" id="resetSubmit">Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var modalEl = document.getElementById('resetModal');
    var modal = null;

    function open(action, ids, label) {
        var deleting = action === 'delete';
        document.getElementById('resetAction').value = action;
        document.getElementById('resetTitle').textContent =
            (deleting ? 'Complete delete ' : 'Cancel ') + (ids.length === 1 ? label : ids.length + ' sales');
        document.getElementById('resetDeleteNote').style.display = deleting ? '' : 'none';
        document.getElementById('resetConfirmWrap').style.display = deleting ? '' : 'none';
        document.getElementById('resetSubmit').textContent = deleting ? 'Complete delete' : 'Cancel sale(s)';
        document.getElementById('resetSubmit').className = deleting ? 'btn btn-danger' : 'btn btn-warning';
        var holder = document.getElementById('resetIds');
        holder.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'order_ids[]'; input.value = id;
            holder.appendChild(input);
        });
        modal = modal || bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    document.querySelectorAll('[data-single]').forEach(function (button) {
        button.addEventListener('click', function () {
            open(button.dataset.single, [button.dataset.id], button.dataset.label);
        });
    });

    document.querySelectorAll('[data-bulk]').forEach(function (button) {
        button.addEventListener('click', function () {
            var picked = Array.prototype.slice.call(document.querySelectorAll('.pick:checked'));
            if (picked.length === 0) { alert('Select at least one sale.'); return; }
            if (button.dataset.bulk === 'delete' && picked.some(function (p) { return p.dataset.blocked === '1'; })) {
                alert('Some selected sales cannot be deleted completely. Unselect them, or use Cancel.');
                return;
            }
            open(button.dataset.bulk, picked.map(function (p) { return p.value; }), '');
        });
    });

    var all = document.getElementById('pickAll');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.pick').forEach(function (box) { box.checked = all.checked; });
        });
    }
})();
</script>
@endsection
