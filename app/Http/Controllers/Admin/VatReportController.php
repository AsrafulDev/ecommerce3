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
        protected \App\Services\Vat\MushakBook $book,
    ) {
        $this->middleware('permission:setting-list|setting-edit', ['only' => ['index', 'export']]);
    }

    /** Registers that can be downloaded. The credit-note lists are CSV only; the PDF is the 6.1 and 6.2 book. */
    public const REGISTERS = ['sales', 'purchase', 'credit-sales', 'credit-purchases'];

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $draft = (new \App\Services\Vat\Mushak91Draft())->build($from, $to, $this->reports);

        return view('backEnd.vat.reports', [
            'from'            => $from,
            'to'              => $to,
            'enabled'         => app(VatCalculator::class)->enabled(),
            'summary'         => $this->reports->monthReturn($from, $to),
            'sales'           => $this->reports->salesRegister($from, $to),
            'purchases'       => $this->reports->purchaseRegister($from, $to),
            'creditSales'     => $this->reports->creditNoteRegister('sale', $from, $to),
            'creditPurchases' => $this->reports->creditNoteRegister('purchase', $from, $to),
            'exceptions'      => $this->reports->exceptions($from, $to),
            'reconciliation'  => $this->reports->reconciliation($from, $to, $draft),
            'categories'      => $this->categoryRows($from, $to),
            'draft91'         => $draft,
            'presets'         => $this->presets(),
        ]);
    }

    /**
     * Sales and purchase VAT by product category, next to the 9.1 line each category feeds. A category with no line is
     * shown as unmapped rather than placed anywhere.
     *
     * @return array<int, array{category: string, label: string, sales_line: ?int, sales: array, purchase_line: ?int, purchases: array}>
     */
    protected function categoryRows(Carbon $from, Carbon $to): array
    {
        $sales = $this->reports->salesByCategory($from, $to);
        $purchases = $this->reports->purchasesByCategory($from, $to);

        $keys = array_values(array_unique(array_merge(array_keys($sales), array_keys($purchases))));
        sort($keys);

        return array_map(fn ($key) => [
            'category'      => $key,
            'label'         => self::CATEGORY_LABELS[$key] ?? ucfirst((string) $key),
            'sales_line'    => config('vat.sales_lines.'.$key),
            'sales'         => $sales[$key] ?? ['taxable' => 0.0, 'vat' => 0.0],
            'purchase_line' => config('vat.purchase_lines.'.$key),
            'purchases'     => $purchases[$key] ?? ['taxable' => 0.0, 'vat' => 0.0],
        ], $keys);
    }

    /** Plain names for the product VAT categories, as the admin shows them. */
    public const CATEGORY_LABELS = [
        'standard' => 'Standard rate',
        'reduced'  => 'Reduced rate',
        'zero'     => 'Zero rate',
        'exempt'   => 'Exempt',
        ''         => 'No category',
    ];

    /**
     * Quick periods for the page. Each is a from/to pair, so the page and its downloads always show the same range.
     *
     * @return array<string, array{0: Carbon, 1: Carbon}>
     */
    protected function presets(): array
    {
        $now = now();

        return [
            'This month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'Last month'   => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'This quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'Last quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
        ];
    }

    /**
     * Download a register as CSV, for one invoice, one day, or a date range.
     * These are working registers, not the official Mushak layout.
     */
    public function export(Request $request, string $type): Response
    {
        abort_unless(in_array($type, self::REGISTERS, true), 404);

        // Links made before the mode existed meant a date range.
        $request->merge(['mode' => $request->input('mode', 'range')]);

        $request->validate([
            'mode'    => 'required|in:invoice,date,range',
            'invoice' => 'required_if:mode,invoice|nullable|string|max:60',
            'date'    => 'required_if:mode,date|nullable|date',
            // Range dates are optional: a missing range means the current month, as before.
            'from'    => 'nullable|date',
            'to'      => 'nullable|date',
            'format'  => 'nullable|in:csv,pdf',
        ]);

        $isCreditList = str_starts_with($type, 'credit-');
        abort_if($isCreditList && $request->input('format') === 'pdf', 422, 'PDF is available for the purchase and sales books only.');

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

        $rows = match ($type) {
            'sales'            => $this->reports->salesRegister($from, $to, $invoice),
            'purchase'         => $this->reports->purchaseRegister($from, $to, $invoice),
            'credit-sales'     => $this->reports->creditNoteRegister('sale', $from, $to, $invoice),
            'credit-purchases' => $this->reports->creditNoteRegister('purchase', $from, $to, $invoice),
        };

        if ($request->input('format') === 'pdf') {
            // The official book layout (Mushak 6.1 purchase, 6.2 sales), for the same selection as the CSV.
            $book = $type === 'sales'
                ? $this->book->salesBook($from, $to, $invoice)
                : $this->book->purchaseBook($from, $to, $invoice);
            $settings = \App\Models\GeneralSetting::orderBy('id', 'desc')->first();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('backEnd.vat.mushak-book', [
                'book'      => $book,
                'business'  => [
                    'name'    => $settings?->vat_business_name,
                    'address' => $settings?->vat_business_address,
                    'bin'     => $settings?->vat_bin,
                ],
                'generated' => now()->format('d M Y H:i'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download(sprintf('mushak-%s-%s.pdf', $type === 'sales' ? '6.2' : '6.1', $label));
        }

        $name = sprintf('vat-%s-%s.csv', $type, $label);

        return response($this->exporter->toCsv($type, $rows), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    private function describeSelection(Request $request, string $type, Carbon $from, Carbon $to, ?string $invoice): string
    {
        if ($invoice !== null) {
            return 'Invoice '.$invoice;
        }
        if ($from->isSameDay($to)) {
            return 'Date '.$from->toDateString();
        }

        return 'Date range '.$from->toDateString().' to '.$to->toDateString();
    }

    /** Defaults to the current month. An inverted range is swapped rather than refused. */
    protected function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }
}
