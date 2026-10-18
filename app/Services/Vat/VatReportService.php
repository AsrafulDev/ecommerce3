<?php

namespace App\Services\Vat;

use App\Models\OrderDetails;
use App\Models\PurchaseItem;
use App\Models\VatCreditNote;
use App\Services\CogsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mushak working data, built from the VAT snapshots on each line. These are draft
 * registers: layout and filing against the official NBR forms are a separate step.
 *
 * A sale is declared in the period it was made (by order date). If it is later returned
 * or cancelled, its credit note reduces output VAT in the period the return happens.
 */
class VatReportService
{
    /**
     * A sale counts as an invoice when it is recognised (delivered or completed), or when
     * it has since been reversed by a credit note, so the original sale stays declared.
     */
    protected function invoicedSale($query, string $orderAlias): void
    {
        $query->whereIn("{$orderAlias}.order_status", CogsCalculator::RECOGNIZED_STATUSES)
            ->orWhereExists(fn ($sub) => $sub->from('vat_credit_notes as cn')
                ->whereColumn('cn.order_id', "{$orderAlias}.id")
                ->where('cn.type', 'sale'));
    }

    /**
     * Credit note totals issued in the period, by type. A return in November reduces
     * November, even when the sale was in October.
     *
     * @return array{taxable: float, vat: float}
     */
    public function creditNoteTotals(string $type, Carbon $from, Carbon $to): array
    {
        $row = VatCreditNote::where('type', $type)
            ->whereBetween('issued_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('COALESCE(SUM(taxable_value), 0) AS taxable, COALESCE(SUM(vat_amount), 0) AS vat')
            ->first();

        return ['taxable' => round((float) $row->taxable, 2), 'vat' => round((float) $row->vat, 2)];
    }

    /**
     * One row per invoice (Mushak 6.2 working data).
     *
     * @return array<int, array{invoice: string, date: string, buyer_bin: ?string, taxable: float, vat: float}>
     */
    public function salesRegister(Carbon $from, Carbon $to): array
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotNull('d.vat_amount')
            ->groupBy('o.id', 'o.invoice_id', 'o.created_at', 'o.buyer_bin')
            ->orderBy('o.created_at')->orderBy('o.id')
            ->selectRaw('o.id, o.invoice_id, o.created_at, o.buyer_bin, SUM(d.taxable_value) AS taxable, SUM(d.vat_amount) AS vat')
            ->get()
            ->map(fn ($row) => [
                'invoice'   => (string) ($row->invoice_id ?: 'Order #'.$row->id),
                'date'      => Carbon::parse($row->created_at)->toDateString(),
                'buyer_bin' => $row->buyer_bin,
                'taxable'   => round((float) $row->taxable, 2),
                'vat'       => round((float) $row->vat, 2),
            ])
            ->all();
    }

    /**
     * One row per purchase invoice (Mushak 6.1 working data), with the supplier's BIN.
     *
     * @return array<int, array{invoice: string, date: string, supplier_bin: ?string, taxable: float, vat: float}>
     */
    public function purchaseRegister(Carbon $from, Carbon $to): array
    {
        return DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('i.vat_amount')
            ->groupBy('p.id', 'p.invoice_no', 'p.purchase_date', 's.name', 's.bin')
            ->orderBy('p.purchase_date')->orderBy('p.id')
            ->selectRaw('p.id, p.invoice_no, p.purchase_date, s.name AS supplier_name, s.bin AS supplier_bin, SUM(i.taxable_value) AS taxable, SUM(i.vat_amount) AS vat')
            ->get()
            ->map(fn ($row) => [
                'invoice'      => (string) ($row->invoice_no ?: 'Purchase #'.$row->id),
                'date'         => Carbon::parse($row->purchase_date)->toDateString(),
                'supplier_name' => $row->supplier_name,
                'supplier_bin' => $row->supplier_bin,
                'taxable'      => round((float) $row->taxable, 2),
                'vat'          => round((float) $row->vat, 2),
            ])
            ->all();
    }

    /**
     * Sales VAT grouped by product VAT category (standard, reduced, zero, exempt, or ''
     * when the product has no category). Used for the 9.1 draft lines.
     *
     * @return array<string, array{taxable: float, vat: float}>
     */
    public function salesByCategory(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'd.product_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotNull('d.vat_amount')
            ->groupBy('p.vat_category')
            ->selectRaw("COALESCE(p.vat_category, '') AS category, SUM(d.taxable_value) AS taxable, SUM(d.vat_amount) AS vat")
            ->get();

        return $rows->mapWithKeys(fn ($r) => [$r->category => [
            'taxable' => round((float) $r->taxable, 2),
            'vat'     => round((float) $r->vat, 2),
        ]])->all();
    }

    /**
     * Purchase VAT grouped by product VAT category, for the 9.1 draft lines.
     *
     * @return array<string, array{taxable: float, vat: float}>
     */
    public function purchasesByCategory(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('i.vat_amount')
            ->groupBy('pr.vat_category')
            ->selectRaw("COALESCE(pr.vat_category, '') AS category, SUM(i.taxable_value) AS taxable, SUM(i.vat_amount) AS vat")
            ->get();

        return $rows->mapWithKeys(fn ($r) => [$r->category => [
            'taxable' => round((float) $r->taxable, 2),
            'vat'     => round((float) $r->vat, 2),
        ]])->all();
    }

    /**
     * Draft figures for the Mushak 9.1 VAT return working: output VAT (sales), input VAT
     * (purchases) and the difference. A positive net is payable; a negative net is a
     * carry-forward. Lines with no VAT snapshot are counted, so gaps are visible.
     *
     * @return array{output_vat: float, input_vat: float, net: float, sales_lines_without_vat: int, purchase_lines_without_vat: int}
     */
    public function monthReturn(Carbon $from, Carbon $to): array
    {
        $salesVat = round(array_sum(array_column($this->salesRegister($from, $to), 'vat')), 2);
        $purchaseVat = round(array_sum(array_column($this->purchaseRegister($from, $to), 'vat')), 2);

        $salesCredit = $this->creditNoteTotals('sale', $from, $to)['vat'];
        $purchaseCredit = $this->creditNoteTotals('purchase', $from, $to)['vat'];

        $output = round($salesVat - $salesCredit, 2);
        $input = round($purchaseVat - $purchaseCredit, 2);

        $salesMissing = OrderDetails::query()
            ->join('orders as o', 'o.id', '=', 'order_details.order_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNull('order_details.vat_amount')
            ->count();

        $purchasesMissing = PurchaseItem::query()
            ->join('purchases as p', 'p.id', '=', 'purchase_items.purchase_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('purchase_items.vat_amount')
            ->count();

        return [
            'output_vat'                   => $output,
            'input_vat'                    => $input,
            'sales_vat'                    => $salesVat,
            'sales_credit_vat'             => $salesCredit,
            'purchase_vat'                 => $purchaseVat,
            'purchase_credit_vat'          => $purchaseCredit,
            'net'                          => round($output - $input, 2),
            'sales_lines_without_vat'      => $salesMissing,
            'purchase_lines_without_vat'   => $purchasesMissing,
        ];
    }
}
