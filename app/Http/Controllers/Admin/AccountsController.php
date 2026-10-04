<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Services\CogsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    public function __construct(protected CogsCalculator $cogs)
    {
    }

    public function dashboard()
    {
        $today      = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart  = $today->copy()->startOfYear();
        $todayEnd   = $today->copy()->addDay();

        // Every money figure below comes from ONE rule — App\Helpers\FundHelper.
        // A 'sale' fund credit is booked when an order is delivered even while
        // payment is still pending (e.g. uncollected COD), so income counts a
        // sale only up to what has actually been collected (the partial rule),
        // never dropping a whole partially-paid order the way the old
        // all-or-nothing filter did. fund_balance therefore equals total_in -
        // total_out exactly, and equals FundHelper::balance().
        $total_in     = FundHelper::income();
        $total_out    = FundHelper::spend();
        $fund_balance = $total_in - $total_out;

        $in_today  = FundHelper::income($today, $todayEnd);
        $out_today = FundHelper::spend($today, $todayEnd);
        $in_month  = FundHelper::income($monthStart);
        $out_month = FundHelper::spend($monthStart);
        $in_year   = FundHelper::income($yearStart);
        $out_year  = FundHelper::spend($yearStart);

        // ── 12-month income vs expense trend (one rule, per month) ──
        $trendStart = $today->copy()->startOfMonth()->subMonths(11);
        $trend = [];
        for ($i = 0; $i < 12; $i++) {
            $mStart = $trendStart->copy()->addMonths($i);
            $mEnd   = $mStart->copy()->addMonth();
            $trend[] = [
                'label' => $mStart->format('M y'),
                'in'    => FundHelper::income($mStart, $mEnd),
                'out'   => FundHelper::spend($mStart, $mEnd),
            ];
        }

        // ── This-month income/expense breakdown by source ──
        $sourceLabels = [
            'sale'             => 'Sales',
            'manual_add'       => 'Owner Capital / Deposit',
            'warranty'         => 'Warranty Charge',
            'warranty_resell'  => 'Warranty Resale',
            'refund_reversal'  => 'Refund Reversal',
            'expense'          => 'Expenses',
            'refund'           => 'Refunds',
            'order_refund'     => 'Order Refunds',
            'supplier_payment' => 'Supplier Payments',
            'employee_salary'  => 'Salaries',
            'employee_bonus'   => 'Bonuses',
            'withdraw'         => 'Owner Withdrawal (Drawings)',
        ];
        $sourceRows = $this->sourceRows(
            $this->sourceBreakdown('in', $monthStart),
            $this->sourceBreakdown('out', $monthStart),
            $sourceLabels
        );

        // ── Gross profit this month — the ONE shared rule (CogsCalculator):
        //    recognized orders by created_at, stored realized COGS per line.──
        $month = $this->cogs->periodProfit($monthStart, now());

        $month_sales  = $month['sales'];
        $month_profit = $month['gross_profit'];

        // ── Position ──
        $stock_value  = (float) StockBatch::where('type', 'in')->where('remaining_qty', '>', 0)
            ->sum(DB::raw('remaining_qty * unit_cost'));
        $supplier_due = (float) Supplier::sum('current_due');
        // Receivable = money earned (order delivered/completed) but not collected.
        // Pending/processing orders are not yet receivables — stock not handed over.
        $customer_due = (float) Order::whereIn('order_status', ['delivered', 'completed'])
            ->whereIn('payment_status', ['pending', 'partial'])
            ->sum('due_amount');

        $recent = FundTransaction::orderByDesc('created_at')->orderByDesc('id')->limit(15)->get();

        return view('backEnd.accounts.dashboard', compact(
            'fund_balance', 'total_in', 'total_out',
            'in_today', 'out_today', 'in_month', 'out_month', 'in_year', 'out_year',
            'trend', 'sourceRows',
            'month_sales', 'month_profit', 'stock_value', 'supplier_due', 'customer_due',
            'recent'
        ));
    }

    /**
     * Per-source movement since $since, using the SAME realizable rule as the
     * headline: the 'sale' line is reduced by this window's uncollected COD, so
     * the income sources add back up to FundHelper::income($since).
     */
    private function sourceBreakdown(string $direction, Carbon $since)
    {
        $rows = FundTransaction::where('direction', $direction)
            ->where('created_at', '>=', $since)
            ->select('source', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        if ($direction === 'in') {
            $canonicalOrderIds = \App\Models\OrderPayment::whereNotNull('fund_transaction_id')->select('order_id');
            $legacySale = (float) FundTransaction::where('direction', 'in')
                ->where('source', 'sale')
                ->whereIn('source_id', $canonicalOrderIds)
                ->where('created_at', '>=', $since)
                ->sum('amount');
            $uncollected = FundHelper::uncollectedSaleCredits($since);
            foreach ($rows as $row) {
                if ($row->source === 'sale') {
                    $row->total = max(0, round((float) $row->total - $legacySale - $uncollected, 2));
                    break;
                }
            }
        }

        return $rows;
    }

    /**
     * Pair income + expense source rows side by side for the breakdown table.
     */
    private function sourceRows($inRows, $outRows, array $labels)
    {
        $rows = [];
        $n    = max($inRows->count(), $outRows->count());
        for ($i = 0; $i < $n; $i++) {
            $in  = $inRows->get($i);
            $out = $outRows->get($i);
            $rows[] = [
                'in_label'  => $in  ? ($labels[$in->source]  ?? ucwords(str_replace('_', ' ', $in->source)))  : null,
                'in_total'  => $in  ? round((float) $in->total, 2)  : null,
                'out_label' => $out ? ($labels[$out->source] ?? ucwords(str_replace('_', ' ', $out->source))) : null,
                'out_total' => $out ? round((float) $out->total, 2) : null,
            ];
        }
        return $rows;
    }
}
