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
    public function salesRegister(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($invoice !== null, fn ($q) => $q->where('o.invoice_id', $invoice))
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
     * One row per sold line (Mushak 6.2 book): the buyer, the invoice and the line's value and VAT.
     *
     * @return array<int, array<string, mixed>>
     */
    public function salesLines(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->leftJoin('shippings as sh', 'sh.order_id', '=', 'o.id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($invoice !== null, fn ($q) => $q->where('o.invoice_id', $invoice))
            ->whereNotNull('d.vat_amount')
            ->orderBy('o.created_at')->orderBy('o.id')->orderBy('d.id')
            ->get([
                'o.id as order_id', 'o.invoice_id', 'o.mushak_invoice_no', 'o.created_at', 'o.buyer_bin',
                'sh.name as buyer_name', 'sh.address as buyer_address',
                'd.product_name', 'd.qty', 'd.taxable_value', 'd.vat_amount',
            ])
            ->map(fn ($row) => [
                'order_id'   => (int) $row->order_id,
                'invoice'    => (string) ($row->mushak_invoice_no ?: ($row->invoice_id ?: 'Order #'.$row->order_id)),
                'date'       => Carbon::parse($row->created_at)->toDateString(),
                'buyer_name' => $row->buyer_name,
                'buyer_address' => $row->buyer_address,
                'buyer_bin'  => $row->buyer_bin,
                'product'    => (string) $row->product_name,
                'qty'        => (float) $row->qty,
                'taxable'    => round((float) $row->taxable_value, 2),
                'vat'        => round((float) $row->vat_amount, 2),
            ])
            ->all();
    }

    /**
     * One row per purchased line (Mushak 6.1 book), with the supplier's name, address and BIN.
     *
     * @return array<int, array<string, mixed>>
     */
    public function purchaseLines(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        return DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->when($invoice !== null, fn ($q) => $q->where('p.invoice_no', $invoice))
            ->whereNotNull('i.vat_amount')
            ->orderBy('p.purchase_date')->orderBy('p.id')->orderBy('i.id')
            ->get([
                'p.id as purchase_id', 'p.invoice_no', 'p.purchase_date',
                's.name as supplier_name', 's.address as supplier_address', 's.bin as supplier_bin',
                'pr.name as product_name', 'i.qty', 'i.taxable_value', 'i.vat_amount',
            ])
            ->map(fn ($row) => [
                'purchase_id'   => (int) $row->purchase_id,
                'invoice'       => (string) ($row->invoice_no ?: 'Purchase #'.$row->purchase_id),
                'date'          => Carbon::parse($row->purchase_date)->toDateString(),
                'supplier_name' => $row->supplier_name,
                'supplier_address' => $row->supplier_address,
                'supplier_bin'  => $row->supplier_bin,
                'product'       => (string) ($row->product_name ?? ''),
                'qty'           => (float) $row->qty,
                'taxable'       => round((float) $row->taxable_value, 2),
                'vat'           => round((float) $row->vat_amount, 2),
            ])
            ->all();
    }

    /**
     * One row per purchase invoice (Mushak 6.1 working data), with the supplier's BIN.
     *
     * @return array<int, array{invoice: string, date: string, supplier_bin: ?string, taxable: float, vat: float}>
     */
    public function purchaseRegister(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        return DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->when($invoice !== null, fn ($q) => $q->where('p.invoice_no', $invoice))
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

    /**
     * One row per credit note issued in the period. A sale credit note reduces output VAT and is traced to its invoice;
     * a purchase credit note reduces input VAT and is traced to its purchase. Dated by when the note was issued.
     *
     * @return array<int, array{number: string, date: string, original: string, party: ?string, taxable: float, vat: float, reason: ?string}>
     */
    public function creditNoteRegister(string $type, Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        return DB::table('vat_credit_notes as cn')
            ->leftJoin('orders as o', 'o.id', '=', 'cn.order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->leftJoin('purchases as p', 'p.id', '=', 'cn.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('cn.type', $type)
            ->whereBetween('cn.issued_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($invoice !== null, fn ($q) => $q->where($type === 'sale' ? 'o.invoice_id' : 'p.invoice_no', $invoice))
            ->orderBy('cn.issued_at')->orderBy('cn.id')
            ->get([
                'cn.credit_note_no', 'cn.issued_at', 'cn.reason', 'cn.taxable_value', 'cn.vat_amount',
                'o.id as order_id', 'o.invoice_id as sale_invoice', 'c.name as customer_name',
                'p.id as purchase_id', 'p.invoice_no as purchase_invoice', 's.name as supplier_name',
            ])
            ->map(fn ($row) => [
                'number'   => (string) $row->credit_note_no,
                'date'     => Carbon::parse($row->issued_at)->toDateString(),
                'original' => $type === 'sale'
                    ? (string) ($row->sale_invoice ?: 'Order #'.$row->order_id)
                    : (string) ($row->purchase_invoice ?: 'Purchase #'.$row->purchase_id),
                'party'    => $type === 'sale' ? $row->customer_name : $row->supplier_name,
                'taxable'  => round((float) $row->taxable_value, 2),
                'vat'      => round((float) $row->vat_amount, 2),
                'reason'   => $row->reason,
            ])
            ->all();
    }

    /**
     * Lines the period cannot report correctly until someone fixes them: sales and purchase lines with no VAT recorded,
     * and products with no VAT category that carry VAT in the period. Nothing here is estimated.
     *
     * @return array{sales: array<int, array<string, mixed>>, purchases: array<int, array<string, mixed>>, uncategorised: array<int, array<string, mixed>>}
     */
    public function exceptions(Carbon $from, Carbon $to): array
    {
        $sales = DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNull('d.vat_amount')
            ->orderBy('o.created_at')->orderBy('o.id')->orderBy('d.id')
            ->get(['o.id as order_id', 'o.invoice_id', 'o.created_at', 'd.product_name', 'd.qty'])
            ->map(fn ($row) => [
                'invoice' => (string) ($row->invoice_id ?: 'Order #'.$row->order_id),
                'date'    => Carbon::parse($row->created_at)->toDateString(),
                'product' => (string) $row->product_name,
                'qty'     => (float) $row->qty,
            ])
            ->all();

        $purchases = DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('i.vat_amount')
            ->orderBy('p.purchase_date')->orderBy('p.id')->orderBy('i.id')
            ->get(['p.id as purchase_id', 'p.invoice_no', 'p.purchase_date', 'pr.name as product_name', 'i.qty'])
            ->map(fn ($row) => [
                'invoice' => (string) ($row->invoice_no ?: 'Purchase #'.$row->purchase_id),
                'date'    => Carbon::parse($row->purchase_date)->toDateString(),
                'product' => (string) ($row->product_name ?? ''),
                'qty'     => (float) $row->qty,
            ])
            ->all();

        $uncategorisedSales = DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where(fn ($q) => $this->invoicedSale($q, 'o'))
            ->whereBetween('o.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotNull('d.vat_amount')
            ->where(fn ($q) => $q->whereNull('p.vat_category')->orWhere('p.vat_category', ''))
            ->groupBy('p.id', 'p.name')
            ->selectRaw('p.name AS product, COUNT(*) AS line_count, SUM(d.vat_amount) AS vat')
            ->get()
            ->map(fn ($row) => ['side' => 'sales', 'product' => (string) $row->product, 'lines' => (int) $row->line_count, 'vat' => round((float) $row->vat, 2)]);

        $uncategorisedPurchases = DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->join('products as pr', 'pr.id', '=', 'i.product_id')
            ->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('i.vat_amount')
            ->where(fn ($q) => $q->whereNull('pr.vat_category')->orWhere('pr.vat_category', ''))
            ->groupBy('pr.id', 'pr.name')
            ->selectRaw('pr.name AS product, COUNT(*) AS line_count, SUM(i.vat_amount) AS vat')
            ->get()
            ->map(fn ($row) => ['side' => 'purchases', 'product' => (string) $row->product, 'lines' => (int) $row->line_count, 'vat' => round((float) $row->vat, 2)]);

        return [
            'sales'         => $sales,
            'purchases'     => $purchases,
            'uncategorised' => $uncategorisedSales->concat($uncategorisedPurchases)->values()->all(),
        ];
    }

    /**
     * The return and the registers must agree. Each check compares two totals built separately, so a difference shows a
     * figure missing from one side. Differences are shown, never adjusted away.
     *
     * @param  array<string, mixed>  $draft  the Mushak 9.1 draft for the same period
     * @return array<int, array{label: string, left: float, right: float, difference: float, agrees: bool}>
     */
    public function reconciliation(Carbon $from, Carbon $to, array $draft): array
    {
        $sum = fn (array $rows, string $key) => round(array_sum(array_column($rows, $key)), 2);

        $salesRegister = $sum($this->salesRegister($from, $to), 'vat');
        $purchaseRegister = $sum($this->purchaseRegister($from, $to), 'vat');

        $salesByCategory = $this->salesByCategory($from, $to);
        $purchasesByCategory = $this->purchasesByCategory($from, $to);
        $salesCategoryVat = round(array_sum(array_column($salesByCategory, 'vat')), 2);
        $purchaseCategoryVat = round(array_sum(array_column($purchasesByCategory, 'vat')), 2);

        $unmappedSalesVat = round(array_sum(array_map(
            fn ($category, $total) => array_key_exists($category, config('vat.sales_lines', [])) ? 0.0 : $total['vat'],
            array_keys($salesByCategory),
            $salesByCategory
        )), 2);

        $creditSales = $sum($this->creditNoteRegister('sale', $from, $to), 'vat');
        $creditPurchases = $sum($this->creditNoteRegister('purchase', $from, $to), 'vat');

        $challans = \App\Models\VatChallan::query()
            ->where('status', \App\Models\VatChallan::ISSUED)
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->get();
        $challanVat = round((float) $challans->sum('total_vat'), 2);
        $challanSalesVat = round((float) \App\Models\OrderDetails::whereIn('order_id', $challans->pluck('order_id'))->sum('vat_amount'), 2);

        $checks = [
            ['Sales: register VAT = VAT by product category', $salesRegister, $salesCategoryVat],
            ['Mushak 6.3 challans: challan VAT = VAT on the same sales', $challanVat, $challanSalesVat],
            ['Sales: VAT on 9.1 lines plus unmapped = register VAT', round(($draft['lines'][9]['vat'] ?? 0.0) + $unmappedSalesVat, 2), $salesRegister],
            ['Purchases: register VAT = VAT by product category', $purchaseRegister, $purchaseCategoryVat],
            ['Sales credit notes: register = credit note total', $creditSales, $this->creditNoteTotals('sale', $from, $to)['vat']],
            ['Purchase credit notes: register = credit note total', $creditPurchases, $this->creditNoteTotals('purchase', $from, $to)['vat']],
        ];

        return array_map(fn ($check) => [
            'label'      => $check[0],
            'left'       => (float) $check[1],
            'right'      => (float) $check[2],
            'difference' => round((float) $check[1] - (float) $check[2], 2),
            'agrees'     => abs((float) $check[1] - (float) $check[2]) < 0.005,
        ], $checks);
    }
}
