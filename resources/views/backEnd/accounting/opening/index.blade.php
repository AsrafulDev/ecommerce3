@extends('backEnd.layouts.master')
@section('title', 'Opening Balances')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Opening Balances / ওপেনিং ব্যালেন্স</h4>
            <small class="text-muted">
                What the business owned and owed on the day the books started. Nothing here reaches a report until you post it.
            </small>
        </div>
        <a href="{{ route('admin.accounting.journals.index', ['source_type' => 'opening']) }}" class="btn btn-sm btn-outline-secondary">
            <i data-feather="list" class="me-1"></i> Opening journals
        </a>
    </div>

    @if (!empty($warnings))
    <div class="card shadow-sm border-0 mb-3 border-start border-warning border-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2">
                <i data-feather="alert-triangle" class="me-1" style="width:16px;height:16px;"></i> Read these before you approve anything
            </h6>
            <p class="text-muted small mb-2">
                The lines below came from the application's own records. Where those records hold two different answers to the same
                question, both are shown — the software will not choose for you, because only you know which one is real money.
            </p>
            <ul class="mb-0 small">
                @foreach ($warnings as $warning)
                    <li class="mb-1">{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.accounting.opening.save') }}" id="openingForm">
        @csrf

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong><i data-feather="calendar" class="me-1" style="width:16px;height:16px;"></i> Balances as at</strong>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="as_at" value="{{ $asAt }}" class="form-control form-control-sm @error('as_at') is-invalid @enderror">
                    @error('as_at')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="card-body p-0">
                <p class="text-muted small px-3 pt-3 mb-2">
                    The day <em>before</em> the first journalled trading day, so the first report shows these as brought-forward
                    balances rather than as money that moved on day one. Every derived line says which query produced it.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="openingLines">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:230px;">Account</th>
                                <th style="min-width:210px;">Description</th>
                                <th style="width:120px;" class="text-end">Debit</th>
                                <th style="width:120px;" class="text-end">Credit</th>
                                <th style="width:150px;">Party</th>
                                <th style="min-width:150px;">How it was worked out</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $i => $line)
                            <tr>
                                <td>
                                    @php $value = (string) ($line['account_id'] ?? ''); @endphp
                                    <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm">
                                        <option value="">— none —</option>
                                        @foreach ($accounts as $option)
                                            <option value="{{ $option->id }}" @selected($value === (string) $option->id)>
                                                {{ $option->code }} — {{ $option->name }}
                                            </option>
                                        @endforeach
                                        @if (!ctype_digit($value) && $value !== '')
                                            {{-- A role the chart no longer carries. Kept visible rather than silently
                                                 turned into "— none —", which would drop the line's account. --}}
                                            <option value="{{ $value }}" selected>{{ $value }} (not in the chart)</option>
                                        @endif
                                    </select>
                                    @error("lines.{$i}.account_id")<span class="text-danger small">{{ $message }}</span>@enderror
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $i }}][description]" class="form-control form-control-sm"
                                           value="{{ $line['description'] ?? '' }}" maxlength="255">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]"
                                           class="form-control form-control-sm text-end" value="{{ $line['debit'] ?? '' }}">
                                    @error("lines.{$i}.debit")<span class="text-danger small">{{ $message }}</span>@enderror
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]"
                                           class="form-control form-control-sm text-end" value="{{ $line['credit'] ?? '' }}">
                                    @error("lines.{$i}.credit")<span class="text-danger small">{{ $message }}</span>@enderror
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <select name="lines[{{ $i }}][party_type]" class="form-select form-select-sm">
                                            <option value="">—</option>
                                            @foreach ($parties as $party)
                                                <option value="{{ $party->value }}" @selected(($line['party_type'] ?? '') === $party->value)>{{ $party->value }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" min="1" name="lines[{{ $i }}][party_id]" class="form-control form-control-sm"
                                               value="{{ $line['party_id'] ?? '' }}" placeholder="id" style="width:66px;">
                                    </div>
                                </td>
                                <td><small class="text-muted">{{ $line['source'] ?? 'keyed in by hand' }}</small></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger removers" title="Remove this line">
                                        <i data-feather="x" style="width:14px;height:14px;"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Totals</td>
                                <td class="text-end fw-bold">{{ number_format((float) $totals['debit'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) $totals['credit'], 2) }}</td>
                                <td colspan="3">
                                    @if ($totals['difference'] === '0.00')
                                        <span class="badge bg-success">Balanced</span>
                                    @else
                                        <span class="badge bg-danger">Out of balance by {{ number_format(abs((float) $totals['difference']), 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white">
                @error('lines')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                @if ($plug)
                <div class="small text-danger mb-2">
                    <strong>Does not balance.</strong>
                    The difference is {{ number_format(abs((float) $plug['credit'] !== '0.00' ? (float) $plug['credit'] : (float) $plug['debit']), 2) }},
                    which would go to <em>{{ $plug['description'] }}</em>. Add it only if you agree that amount is what the owner has in
                    the business — a plug is where a missing line hides.
                </div>
                @endif

                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addLine">
                        <i data-feather="plus" class="me-1" style="width:14px;height:14px;"></i> Add a line
                    </button>

                    <div class="text-muted small">
                        {{ $draft ? "Saved as draft {$draft->journal_no} — in no report." : 'Not saved yet — nothing is in the books.' }}
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i data-feather="save" class="me-1" style="width:14px;height:14px;"></i> Save as draft
                        </button>
                        @if ($plug)
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                formaction="{{ route('admin.accounting.opening.balance') }}">
                            Add the balancing line
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if (!empty($missing))
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>What the records cannot tell us</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                None of this is in the lines above. Add it yourself if any of it applies — whatever is left out of an opening balance
                is later read back as owner capital, because that is where an unexplained difference always lands.
            </p>
            <ul class="small mb-0">
                @foreach ($missing as $gap)
                    <li class="mb-1">{{ $gap }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light"><strong>Post it</strong></div>
        <div class="card-body">
            @if (!$draft)
                <p class="text-muted small mb-3">Save the worksheet as a draft first, then come back. Posting is deliberately a separate act from agreeing the numbers.</p>
            @elseif ($totals['difference'] !== '0.00')
                <p class="text-muted small mb-3">
                    This cannot be posted while debits and credits differ by {{ number_format(abs((float) $totals['difference']), 2) }}.
                    An unbalanced journal is not a rounding problem to wave through — a line is missing or a figure is wrong.
                </p>
            @else
                <p class="small mb-2">Balanced at {{ number_format((float) $totals['debit'], 2) }} across {{ $totals['lines'] }} lines, as at {{ $asAt }}.</p>
            @endif

            @php $ready = $draft && $totals['difference'] === '0.00'; @endphp

            <form method="POST" action="{{ route('admin.accounting.opening.post') }}"
                  onsubmit="return confirm('Posting writes the opening balances permanently. Afterwards they can only be corrected by reversing the journal. Continue?');">
                @csrf
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="confirmed" value="1" id="openingConfirmed" @disabled(!$ready)>
                    <label class="form-check-label small" for="openingConfirmed">
                        I have checked these figures against the till, the bank statements, the stock and what I actually owe — not only against this screen.
                    </label>
                </div>
                <button type="submit" class="btn btn-danger" @disabled(!$ready)>
                    <i data-feather="check-circle" class="me-1" style="width:14px;height:14px;"></i> Post opening balances
                </button>
            </form>

            @if ($draft)
            <form method="POST" action="{{ route('admin.accounting.opening.discard') }}" class="mt-2"
                  onsubmit="return confirm('Discard the draft? The screen then re-derives from the records, so you lose your edits, not your data.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-link text-muted p-0">Discard this draft and start from the records again</button>
            </form>
            @endif
        </div>
    </div>

</div>
@endsection

@push('js')
<script>
(function () {
    var table = document.querySelector('#openingLines tbody');
    var add = document.getElementById('addLine');
    if (!table || !add) return;

    // Rows are named by position, so every row is renumbered after an add or a
    // remove rather than leaving two rows writing into the same key.
    function renumber() {
        Array.prototype.forEach.call(table.rows, function (row, index) {
            row.querySelectorAll('input, select').forEach(function (field) {
                field.name = field.name.replace(/lines\[\d+\]/, 'lines[' + index + ']');
            });
        });
    }

    add.addEventListener('click', function () {
        var row = table.rows[table.rows.length - 1].cloneNode(true);

        row.querySelectorAll('input').forEach(function (field) { if (field.type !== 'hidden') field.value = ''; });
        row.querySelectorAll('select').forEach(function (field) { field.selectedIndex = 0; });

        table.appendChild(row);
        renumber();

        if (window.feather) feather.replace();
    });

    table.addEventListener('click', function (event) {
        var button = event.target.closest('.removers');
        if (!button) return;

        button.closest('tr').remove();
        renumber();
    });
})();
</script>
@endpush
