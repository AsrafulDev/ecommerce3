<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Vat\VatCsvExporter;
use App\Services\Vat\VatCalculator;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Mushak working reports: the two registers and the VAT return summary (9.1 working).
 * Draft figures. Filing against the official forms is a separate, checked step.
 */
class VatReportController extends Controller
{
    public function __construct(
        protected VatReportService $reports,
        protected VatCsvExporter $exporter,
    ) {
        $this->middleware('permission:setting-list|setting-edit', ['only' => ['index', 'export']]);
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        return view('backEnd.vat.reports', [
            'from'        => $from,
            'to'          => $to,
            'enabled'     => app(VatCalculator::class)->enabled(),
            'summary'     => $this->reports->monthReturn($from, $to),
            'sales'       => $this->reports->salesRegister($from, $to),
            'purchases'   => $this->reports->purchaseRegister($from, $to),
            'draft91'     => (new \App\Services\Vat\Mushak91Draft())->build($from, $to, $this->reports),
        ]);
    }

    /**
     * Download a register as CSV, for one invoice, one day, or a date range.
     * These are working registers, not the official Mushak layout.
     */
    public function export(Request $request, string $type): Response
    {
        abort_unless(in_array($type, ['sales', 'purchase'], true), 404);

        // Links made before the mode existed meant a date range.
        $request->merge(['mode' => $request->input('mode', 'range')]);

        $request->validate([
            'mode'    => 'required|in:invoice,date,range',
            'invoice' => 'required_if:mode,invoice|nullable|string|max:60',
            'date'    => 'required_if:mode,date|nullable|date',
            // Range dates are optional: a missing range means the current month, as before.
            'from'    => 'nullable|date',
            'to'      => 'nullable|date',
        ]);

        $invoice = null;
        switch ($request->input('mode')) {
            case 'invoice':
                $invoice = trim((string) $request->input('invoice'));
                $from = Carbon::create(2000, 1, 1);
                $to = Carbon::create(2100, 12, 31);
                $label = 'invoice-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice);
                break;
            case 'date':
                $from = $to = Carbon::parse($request->input('date'));
                $label = 'date-'.$from->toDateString();
                break;
            default:
                [$from, $to] = $this->period($request);
                $label = 'range-'.$from->toDateString().'-to-'.$to->toDateString();
        }

        $rows = $type === 'sales'
            ? $this->reports->salesRegister($from, $to, $invoice)
            : $this->reports->purchaseRegister($from, $to, $invoice);

        $name = sprintf('vat-%s-%s.csv', $type, $label);

        return response($this->exporter->toCsv($type, $rows), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    /** Defaults to the current month. An inverted range is swapped rather than refused. */
    protected function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }
}
