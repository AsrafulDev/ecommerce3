{{--
  File Warranty Claim Page — Customer
--}}
@php
    $warrantySale = \App\Models\WarrantySale::with('product')->find(request('warranty_sale_id'));
    if(!$warrantySale) { echo 'Warranty not found.'; return; }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Warranty Claim — {{ $warrantySale->product->name ?? 'Product' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 700px;">
    <h4 class="mb-4">🔧 File Warranty Claim</h4>

    <div class="card mb-4">
        <div class="card-body">
            <h6>{{ $warrantySale->product->name ?? 'Product' }}</h6>
            <p class="mb-1 text-muted">
                Warranty: {{ $warrantySale->warranty_days }} Days |
                Remaining: <strong>{{ $warrantySale->remaining_days }} days</strong> |
                Expires: {{ $warrantySale->warranty_end_date?->format('d M, Y') ?? 'N/A' }}
            </p>
        </div>
    </div>

    <form action="{{ route('customer.warranty.submit-claim') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="warranty_sale_id" value="{{ $warrantySale->id }}">

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label fw-bold">Issue Type</label>
                <select name="issue_type" class="form-select" required>
                    <option value="">— Select —</option>
                    <option value="defective">Defective product</option>
                    <option value="not_working">Not working as expected</option>
                    <option value="damaged">Physical damage (covered)</option>
                    <option value="missing_parts">Missing parts/accessories</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label fw-bold">Describe the Issue</label>
                <textarea name="issue_description" class="form-control" rows="5"
                    placeholder="Please describe what's wrong with the product..." required></textarea>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <label class="form-label fw-bold">Attachments (Optional)</label>
                <input type="file" name="attachments[]" class="form-control" multiple
                    accept="image/*,video/*" data-max="5">
                <small class="text-muted">Max 5 files, 10MB each</small>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body bg-warning bg-opacity-10">
                <h6>⚠️ Warranty Terms Reminder</h6>
                <ul class="mb-0 small">
                    <li>Physical damage from misuse is NOT covered</li>
                    <li>Water damage is NOT covered</li>
                    <li>Normal wear & tear is NOT covered</li>
                </ul>
            </div>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" id="confirmTerms" required>
            <label class="form-check-label" for="confirmTerms">
                I confirm the issue is covered under warranty terms
            </label>
        </div>

        <button type="submit" class="btn btn-danger w-100 btn-lg">📤 Submit Claim</button>
    </form>
</div>
</body>
</html>
