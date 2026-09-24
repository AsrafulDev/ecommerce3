<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FundHelper;
use App\Http\Controllers\Controller;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\StockBatch;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    public function dashboard()
    {
        $today      = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart  = $today->copy()->startOfYear();

        $fund_balance = FundHelper::balance();
        $total_in     = FundTransaction::where('direction', 'in')->sum('amount');
        $total_out    = FundTransaction::where('direction', 'out')->sum('amount');

        $in_today  = (float) FundTransaction::where('direction', 'in')->whereDate('created_at', $today)->sum('amount');
        $out_today = (float) FundTransaction::where('direction', 'out')->whereDate('created_at', $today)->sum('amount');
        $in_month  = (float) FundTransaction::where('direction', 'in')->where('created_at', '>=', $monthStart)->sum('amount');
        $out_month = (float) FundTransaction::where('direction', 'out')->where('created_at', '>=', $monthStart)->sum('amount');
        $in_year   = (float) FundTransaction::where('direction', 'in')->where('created_at', '>=', $yearStart)->sum('amount');
        $out_year  = (float) FundTransaction::where('direction', 'out')->where('created_at', '>=', $yearStart)->sum('amount');

        // ── 12-month income vs expense trend (one grouped query) ──
        $trendStart = $today->copy()->startOfMonth()->subMonths(11);
        $rows = FundTransaction::where('created_at', '>=', $trendStart)
            ->select('direction', DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('SUM(amount) as total'))
            ->groupBy('direction', 'ym')
            ->get();

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $trendStart->copy()->addMonths($i);
            $months[$m->format('Y-m')] = ['label' => $m->format('M y'), 'in' => 0.0, 'out' => 0.0];
        }
        foreach ($rows as $r) {
            if (isset($months[$r->ym])) {
                $months[$r->ym][$r->direction] = round((float) $r->total, 2);
            }
        }
        $trend = array_values($months);

        // ── This-month income/expense breakdown by source ──
        $sourceLabels = [
            'sale'             => 'Sales',
            'manual_add'       => 'Manual Add',
            'warranty'         => 'Warranty Charge',
            'warranty_resell'  => 'Warranty Resale',
            'refund_reversal'  => 'Refund Reversal',
            'expense'          => 'Expenses',
            'refund'           => 'Refunds',
            'order_refund'     => 'Order Refunds',
            'supplier_payment' => 'Supplier Payments',
            'employee_salary'  => 'Salaries',
            'employee_bonus'   => 'Bonuses',
            'withdraw'         => 'Withdrawals',
        ];
        $sourceRows = $this->sourceRows(
            $this->sourceBreakdown('in', $monthStart),
            $this->sourceBreakdown('out', $monthStart),
            $sourceLabels
        );

        // ── Gross profit this month (delivered/completed orders, batch-realized COGS) ──
        $monthOrders = Order::whereIn('order_status', ['delivered', 'completed'])
            ->whereBetween('updated_at', [$monthStart, now()])
            ->get();
        $month_sales = (float) $monthOrders->sum('amount');

        $monthCogs = 0.0;
        $details = OrderDetails::whereIn('order_id', $monthOrders->pluck('id'))->get();
        foreach ($details as $row) {
            if ($row->cogs !== null && (float) $row->cogs > 0) {
                $monthCogs += (float) $row->cogs;
            } else {
                $monthCogs += (($row->purchase_price ?? 0) * $row->qty);
            }
        }
        $month_profit = $month_sales - $monthCogs;

        // ── Position ──
        $stock_value  = (float) StockBatch::where('type', 'in')->where('remaining_qty', '>', 0)
            ->sum(DB::raw('remaining_qty * unit_cost'));
        $supplier_due = (float) Supplier::sum('current_due');
        $customer_due = (float) Order::whereIn('payment_status', ['pending', 'partial'])->sum('due_amount');

        $recent = FundTransaction::orderByDesc('created_at')->orderByDesc('id')->limit(15)->get();

        return view('backEnd.accounts.dashboard', compact(
            'fund_balance', 'total_in', 'total_out',
            'in_today', 'out_today', 'in_month', 'out_month', 'in_year', 'out_year',
            'trend', 'sourceRows',
            'month_sales', 'month_profit', 'stock_value', 'supplier_due', 'customer_due',
            'recent'
        ));
    }

    private function sourceBreakdown(string $direction, Carbon $since)
    {
        return FundTransaction::where('direction', $direction)
            ->where('created_at', '>=', $since)
            ->select('source', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();
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
