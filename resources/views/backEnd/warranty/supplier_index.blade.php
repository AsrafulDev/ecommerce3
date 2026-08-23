@extends('backEnd.layouts.master')
@section('title', 'Supplier Warranties')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📦 Supplier Warranties</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierWarrantyModal">
            <i class="fa fa-plus me-1"></i> Add Supplier Warranty
        </button>
    </div>

    {{-- 🔍 Filter / Search --}}
    <div class="card mb-3">
        <div class="card-header"><strong>🔍 Filter / Search</strong></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.warranty.supplier.index') }}" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Supplier, product, barcode, terms...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Batch No.</label>
                    <input type="text" name="batch" value="{{ request('batch') }}" class="form-control" placeholder="e.g. DEMO-1-A">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) request('supplier') === (string) $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') == 'active')>Active</option>
                        <option value="expired" @selected(request('status') == 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Warranty Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\WarrantyType::cases() as $t)
                            <option value="{{ $t->value }}" @selected(request('type') == $t->value)>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transferable</label>
                    <select name="transferable" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('transferable') == '1')>Yes</option>
                        <option value="0" @selected(request('transferable') == '0')>No</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="fa fa-search me-1"></i> Search</button>
                    <a href="{{ route('admin.warranty.supplier.index') }}" class="btn btn-secondary"><i class="fa fa-refresh me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Product</th>
                            <th>Warranty Days</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Remaining</th>
                            <th>Transferable</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warranties as $w)
                        <tr>
                            <td>{{ $w->id }}</td>
                            <td>{{ $w->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $w->product->name ?? 'N/A' }}</td>
                            <td>{{ $w->warranty_days }} days</td>
                            <td>{{ $w->warranty_start_date?->format('d M, Y') ?? 'N/A' }}</td>
                            <td>{{ $w->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</td>
                            <td>
                                @if($w->is_valid)
                                    <span class="badge bg-success">{{ $w->remaining_days }} days</span>
                                @else
                                    <span class="badge bg-danger">Expired</span>
                                @endif
                            </td>
                            <td>{{ $w->is_transferable ? '✅' : '❌' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal{{ $w->id }}" title="View details"><i class="fa fa-eye me-1"></i>View</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No supplier warranties found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $warranties->links() }}
        </div>
    </div>
</div>

{{-- ➕ Add Supplier Warranty Modal --}}
<div class="modal fade" id="addSupplierWarrantyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.warranty.supplier.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">➕ Add Supplier Warranty</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" id="add_supplier_id" required>
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" id="add_product_id" required>
                            <option value="">— Select Product —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Purchase Item (optional)</label>
                        <select name="purchase_item_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($purchaseItems as $pi)
                                <option value="{{ $pi->id }}">#{{ $pi->id }} · {{ $pi->product->name ?? 'Product' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Batch (optional)</label>
                        <select name="batch_id" class="form-select" id="add_batch_id">
                            <option value="">— None —</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" data-product-id="{{ $b->product_id }}" data-supplier-id="{{ $b->supplier_id }}">{{ $b->batch_no ?: 'Batch #'.$b->id }} · {{ $b->product->name ?? 'Product' }} ({{ $b->remaining_qty }} left)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Warranty Days <span class="text-danger">*</span></label>
                        <input type="number" name="warranty_days" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="warranty_start_date" class="form-control">
                    </div>
                    <div class="col-12 mb-2">
                        <label class="form-label">Warranty Terms</label>
                        <input type="text" name="warranty_terms" class="form-control" placeholder="e.g. 1:1 exchange within warranty period">
                    </div>
                    <div class="col-12 mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_transferable" id="add_is_transferable" value="1" checked>
                            <label class="form-check-label" for="add_is_transferable">Transferable to customer warranty</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Warranty</button>
            </div>
        </form>
    </div>
</div>

{{-- 📋 View + ✏️ Edit Modals --}}
@foreach($warranties as $w)
<div class="modal fade" id="viewModal{{ $w->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">📋 Supplier Warranty #{{ $w->id }} — Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <th style="width:38%">Supplier</th>
                        <td>
                            <strong>{{ $w->supplier->name ?? 'N/A' }}</strong>
                            @if($w->supplier?->phone)<div class="small text-muted"><i class="fa fa-phone me-1"></i>{{ $w->supplier->phone }}</div>@endif
                            @if($w->supplier?->email)<div class="small text-muted"><i class="fa fa-envelope me-1"></i>{{ $w->supplier->email }}</div>@endif
                            @if($w->supplier?->address)<div class="small text-muted"><i class="fa fa-map-marker me-1"></i>{{ $w->supplier->address }}</div>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Product</th>
                        <td>
                            <strong>{{ $w->product->name ?? 'N/A' }}</strong>
                            @if($w->product?->barcode)<div class="small text-muted" style="font-family:monospace">Barcode: {{ $w->product->barcode }}</div>@endif
                            @if($w->product?->product_code)<div class="small text-muted">Code: {{ $w->product->product_code }}</div>@endif
                        </td>
                    </tr>
                    <tr>
                        <th>Purchase</th>
                        <td>
                            @if($w->purchaseItem)
                                @if($w->purchaseItem->purchase)
                                    <strong>Invoice #{{ $w->purchaseItem->purchase->invoice_no ?? $w->purchaseItem->purchase->id }}</strong>
                                    @if($w->purchaseItem->purchase->purchase_date)<div class="small text-muted">Date: {{ $w->purchaseItem->purchase->purchase_date?->format('d M, Y') }}</div>@endif
                                @else
                                    Purchase Item #{{ $w->purchaseItem->id }}
                                @endif
                            @else
                                <span class="text-muted">Not linked</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Batch</th>
                        <td>
                            @if($w->batch)
                                <strong>{{ $w->batch->batch_no ?: 'Batch #'.$w->batch->id }}</strong>
                                @if($w->batch->remaining_qty !== null)<div class="small text-muted">Remaining: {{ $w->batch->remaining_qty }}</div>@endif
                            @else
                                <span class="text-muted">Not linked</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Warranty Type</th><td>{{ ucfirst($w->warranty_type ?? 'supplier_warranty') }}</td></tr>
                    <tr><th>Warranty Days</th><td>{{ $w->warranty_days }} days</td></tr>
                    <tr><th>Start Date</th><td>{{ $w->warranty_start_date?->format('d M, Y') ?? 'N/A' }}</td></tr>
                    <tr><th>End Date</th><td>{{ $w->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</td></tr>
                    <tr>
                        <th>Remaining</th>
                        <td>
                            @if($w->is_valid)<span class="badge bg-success">{{ $w->remaining_days }} days</span>
                            @else<span class="badge bg-danger">Expired</span>@endif
                            @if($w->is_sellable)<span class="badge bg-info ms-1">Sellable</span>@endif
                        </td>
                    </tr>
                    <tr><th>Transferable</th><td>{{ $w->is_transferable ? '✅ Yes' : '❌ No' }}</td></tr>
                    <tr><th>Warranty Terms</th><td>{{ $w->warranty_terms ?? '—' }}</td></tr>
                    <tr><th>Notes</th><td>{{ $w->notes ?? '—' }}</td></tr>
                    <tr>
                        <th>Usage</th>
                        <td>
                            <span class="badge bg-secondary">{{ $w->warrantySales->count() }} warranty sale(s)</span>
                            <span class="badge bg-warning ms-1">{{ $w->warrantySales->sum('claims_count') }} claim(s)</span>
                        </td>
                    </tr>
                    <tr><th>Created</th><td>{{ $w->created_at?->format('d M, Y h:i A') ?? 'N/A' }}</td></tr>
                    <tr><th>Last Updated</th><td>{{ $w->updated_at?->format('d M, Y h:i A') ?? 'N/A' }}</td></tr>
                </table>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-info"
                            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#editModal{{ $w->id }}">
                        <i class="fa fa-edit me-1"></i> Edit
                    </button>
                    <form action="{{ route('admin.warranty.supplier.destroy', $w) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this supplier warranty?')">
                        @csrf
                        <button class="btn btn-danger"><i class="fa fa-trash me-1"></i> Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal{{ $w->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.warranty.supplier.update') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="supplier_warranty_id" value="{{ $w->id }}">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">✏️ Edit Supplier Warranty #{{ $w->id }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select edit-supplier-id" data-warranty-id="{{ $w->id }}" required>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ $w->supplier_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select edit-product-id" data-warranty-id="{{ $w->id }}" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ $w->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Purchase Item (optional)</label>
                        <select name="purchase_item_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($purchaseItems as $pi)
                                <option value="{{ $pi->id }}" {{ $w->purchase_item_id == $pi->id ? 'selected' : '' }}>#{{ $pi->id }} · {{ $pi->product->name ?? 'Product' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Batch (optional)</label>
                        <select name="batch_id" class="form-select edit-batch-id" data-warranty-id="{{ $w->id }}">
                            <option value="">— None —</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" data-product-id="{{ $b->product_id }}" data-supplier-id="{{ $b->supplier_id }}" {{ $w->batch_id == $b->id ? 'selected' : '' }}>{{ $b->batch_no ?: 'Batch #'.$b->id }} · {{ $b->product->name ?? 'Product' }} ({{ $b->remaining_qty }} left)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Warranty Days <span class="text-danger">*</span></label>
                        <input type="number" name="warranty_days" class="form-control" min="0" value="{{ $w->warranty_days }}" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="warranty_start_date" class="form-control" value="{{ $w->warranty_start_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-12 mb-2">
                        <label class="form-label">Warranty Terms</label>
                        <input type="text" name="warranty_terms" class="form-control" value="{{ $w->warranty_terms }}">
                    </div>
                    <div class="col-12 mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes...">{{ $w->notes }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_transferable" id="edit_is_transferable_{{ $w->id }}" value="1" {{ $w->is_transferable ? 'checked' : '' }}>
                            <label class="form-check-label" for="edit_is_transferable_{{ $w->id }}">Transferable to customer warranty</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info">Update Warranty</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
    // 🔎 Filter batch dropdown by the selected product + supplier (Add + Edit modals)
    (function () {
        function filterBatches(productSelect, supplierSelect, batchSelect) {
            if (!productSelect || !batchSelect) return;
            var productId = productSelect.value;
            var supplierId = supplierSelect ? supplierSelect.value : '';
            var selectedBatch = batchSelect.value; // remember current selection
            var firstMatch = null;

            Array.from(batchSelect.options).forEach(function (opt) {
                if (opt.value === '') return; // keep the "— None —" option
                var optProduct = opt.getAttribute('data-product-id');
                var optSupplier = opt.getAttribute('data-supplier-id');
                var show = true;
                if (productId && optProduct !== productId) show = false;
                if (supplierId && optSupplier !== supplierId) show = false;
                opt.style.display = show ? '' : 'none';
                if (show && !firstMatch) firstMatch = opt.value;
            });

            // If the currently selected batch no longer matches, auto-select
            // the first matching batch (or "None" if nothing matches).
            var selectedStillVisible = selectedBatch && Array.from(batchSelect.options)
                .some(function (o) { return o.value === selectedBatch && o.style.display !== 'none'; });
            if (!selectedStillVisible) {
                batchSelect.value = firstMatch || '';
            }
        }

        // Add modal
        var addProduct = document.getElementById('add_product_id');
        var addSupplier = document.getElementById('add_supplier_id');
        var addBatch = document.getElementById('add_batch_id');
        if (addProduct && addBatch) {
            var addSync = function () { filterBatches(addProduct, addSupplier, addBatch); };
            addProduct.addEventListener('change', addSync);
            if (addSupplier) addSupplier.addEventListener('change', addSync);
        }

        // Edit modals (one per warranty)
        document.querySelectorAll('.edit-product-id').forEach(function (productSelect) {
            var wid = productSelect.getAttribute('data-warranty-id');
            var batchSelect = document.querySelector('.edit-batch-id[data-warranty-id="' + wid + '"]');
            var supplierSelect = document.querySelector('.edit-supplier-id[data-warranty-id="' + wid + '"]');
            if (batchSelect) {
                var editSync = function () { filterBatches(productSelect, supplierSelect, batchSelect); };
                productSelect.addEventListener('change', editSync);
                if (supplierSelect) supplierSelect.addEventListener('change', editSync);

                // Apply the filter as soon as the edit modal opens (pre-selected values)
                var editModal = document.getElementById('editModal' + wid);
                if (editModal) {
                    editModal.addEventListener('shown.bs.modal', editSync);
                }
            }
        });
    })();
</script>
@endsection
