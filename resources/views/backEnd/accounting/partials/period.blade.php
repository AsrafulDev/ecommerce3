<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label mb-1 small text-muted">{{ __('From') }}</label>
        <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label mb-1 small text-muted">{{ __('To') }}</label>
        <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
    </div>
    @foreach (($keep ?? []) as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
    @can('accounting-export')
        @if (!empty($reportKey))
            <div class="col-auto ms-auto">
                <a class="btn btn-sm btn-outline-secondary" target="_blank"
                   href="{{ route('admin.accounting.reports.print', ['report' => $reportKey, 'from' => $from, 'to' => $to]) }}">
                    {{ __('Print / PDF') }}
                </a>
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('admin.accounting.reports.export', ['report' => $reportKey, 'from' => $from, 'to' => $to]) }}">
                    {{ __('CSV') }}
                </a>
            </div>
        @endif
    @endcan
</form>
