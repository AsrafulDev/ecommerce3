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

    public function export(Request $request, string $type): Response
    {
        [$from, $to] = $this->period($request);

        $rows = match ($type) {
            'sales'    => $this->reports->salesRegister($from, $to),
            'purchase' => $this->reports->purchaseRegister($from, $to),
            default    => abort(404),
        };

        $name = sprintf('vat-%s-%s-to-%s.csv', $type, $from->toDateString(), $to->toDateString());

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
