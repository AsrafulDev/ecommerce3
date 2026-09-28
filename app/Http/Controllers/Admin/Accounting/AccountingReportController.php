<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Softmit\DoubleEntry\Services\LedgerService;
use Softmit\DoubleEntry\Services\ProfitAndLossReport;
use Softmit\DoubleEntry\Services\TrialBalanceReport;

/**
 * Period reports, all derived from posted journals through one shared query.
 *
 * Print and CSV render the SAME array the screen shows, so an exported figure
 * can never disagree with the page it was exported from.
 */
class AccountingReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-list', ['only' => ['trialBalance', 'profitLoss', 'cash']]);
        $this->middleware('permission:accounting-export', ['only' => ['print', 'export']]);
    }

    public function trialBalance(Request $request, TrialBalanceReport $report)
    {
        [$from, $to] = $this->period($request);

        return view('backEnd.accounting.reports.trial-balance', [
            'report' => $report->build($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function profitLoss(Request $request, ProfitAndLossReport $report)
    {
        [$from, $to] = $this->period($request);

        return view('backEnd.accounting.reports.profit-loss', [
            'report' => $report->build($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function cash(Request $request, LedgerService $ledgers)
    {
        [$from, $to] = $this->period($request);

        return view('backEnd.accounting.reports.cash', [
            'positions' => $ledgers->cashPositions($from, $to),
            'total' => $ledgers->totalCash($to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Printable statement: ?report=trial-balance|profit-loss|cash
     */
    public function print(Request $request, TrialBalanceReport $trialBalance, ProfitAndLossReport $profitLoss, LedgerService $ledgers)
    {
        [$from, $to] = $this->period($request);
        $which = $request->input('report', 'trial-balance');

        $data = match ($which) {
            'profit-loss' => ['report' => $profitLoss->build($from, $to)],
            'cash' => ['positions' => $ledgers->cashPositions($from, $to), 'total' => $ledgers->totalCash($to)],
            default => ['report' => $trialBalance->build($from, $to)],
        };

        $pdf = Pdf::loadView('backEnd.accounting.reports.print', [
            'which' => $which,
            'from' => $from,
            'to' => $to,
        ] + $data)->setPaper('a4', 'portrait');

        return $pdf->stream("accounting-{$which}-{$to}.pdf");
    }

    public function export(Request $request, TrialBalanceReport $trialBalance, ProfitAndLossReport $profitLoss, LedgerService $ledgers)
    {
        [$from, $to] = $this->period($request);
        $which = $request->input('report', 'trial-balance');

        $rows = match ($which) {
            'profit-loss' => $this->profitLossRows($profitLoss->build($from, $to)),
            'cash' => $this->cashRows($ledgers->cashPositions($from, $to)),
            default => $this->trialBalanceRows($trialBalance->build($from, $to)),
        };

        if (empty($rows)) {
            return back()->with('error', 'Nothing to export for this period.');
        }

        $filename = "accounting-{$which}-{$from}-{$to}.csv";
        $headers = array_keys($rows[0]);

        // Same pattern as the fund export: streamed, with a BOM so Excel reads
        // the currency symbols as UTF-8.
        return response()->streamDownload(function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function period(Request $request): array
    {
        $to = $request->input('to') ?: date('Y-m-d');
        $from = $request->input('from') ?: date('Y-m-01', strtotime($to));

        return [$from, $to];
    }

    protected function trialBalanceRows(array $report): array
    {
        $rows = [];

        foreach ($report['rows'] as $row) {
            $rows[] = [
                'Code' => $row['code'],
                'Account' => $row['name'],
                'Type' => $row['account_type'],
                'Opening' => $row['opening'],
                'Debit' => $row['debit'],
                'Credit' => $row['credit'],
                'Closing' => $row['closing'],
            ];
        }

        $rows[] = [
            'Code' => '',
            'Account' => 'TOTAL',
            'Type' => '',
            'Opening' => '',
            'Debit' => $report['totals']['movement_debit'],
            'Credit' => $report['totals']['movement_credit'],
            'Closing' => $report['balanced'] ? 'BALANCED' : 'DIFFERENCE ' . $report['difference'],
        ];

        return $rows;
    }

    protected function profitLossRows(array $report): array
    {
        $rows = [];

        foreach ($report['groups'] as $label => $group) {
            foreach ($group['rows'] as $row) {
                $rows[] = [
                    'Section' => $group['label'],
                    'Code' => $row['code'],
                    'Account' => $row['name'],
                    'Amount' => $row['amount'],
                ];
            }

            $rows[] = [
                'Section' => $group['label'],
                'Code' => '',
                'Account' => 'Subtotal',
                'Amount' => $group['total'],
            ];
        }

        foreach ([
            'Gross profit' => $report['gross_profit'],
            'Net profit' => $report['net_profit'],
        ] as $label => $amount) {
            $rows[] = ['Section' => 'Result', 'Code' => '', 'Account' => $label, 'Amount' => $amount];
        }

        return $rows;
    }

    protected function cashRows(array $positions): array
    {
        $rows = [];

        foreach ($positions as $key => $position) {
            $rows[] = [
                'Fund' => $key,
                'Label' => $position['label'],
                'Account' => $position['account_code'] . ' ' . $position['account_name'],
                'Opening' => $position['opening'],
                'In' => $position['in'],
                'Out' => $position['out'],
                'Closing' => $position['closing'],
            ];
        }

        return $rows;
    }
}
