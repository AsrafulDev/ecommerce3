<?php

namespace App\Services\Vat;

use Carbon\Carbon;

/**
 * DRAFT Mushak 9.1 (VAT return) working figures.
 *
 * Line numbers are taken from the official NBR form, Mushak-9.1 (official-forms/).
 * How each product VAT category maps onto a line is OUR assumption, marked in MAPPING.
 * A practitioner must confirm the mapping before any figure is used for filing.
 */
class Mushak91Draft
{
    public function build(Carbon $from, Carbon $to, VatReportService $reports): array
    {
        $sales = $reports->salesByCategory($from, $to);
        $purchases = $reports->purchasesByCategory($from, $to);
        $salesCredit = $reports->creditNoteTotals('sale', $from, $to);
        $purchaseCredit = $reports->creditNoteTotals('purchase', $from, $to);

        $lines = [];
        $unmapped = [];

        // ── Part 3: sales (lines 1–8, total 9)
        $salesLines = config('vat.sales_lines', []);
        $purchaseLines = config('vat.purchase_lines', []);

        foreach ($sales as $category => $total) {
            if (!isset($salesLines[$category])) {
                $unmapped['sales'][$category ?: 'no category'] = $total;
                continue;
            }
            $this->add($lines, $salesLines[$category], $total['taxable'], 0, $total['vat']);
        }
        $salesVat = array_sum(array_column($lines, 'vat'));
        $this->add($lines, 9, $this->sumValue($lines), 0, round($salesVat, 2));

        // ── Part 4: purchases (lines 10–22, total 23)
        $purchaseVat = 0.0;
        foreach ($purchases as $category => $total) {
            if (!isset($purchaseLines[$category])) {
                $unmapped['purchases'][$category ?: 'no category'] = $total;
                continue;
            }
            $this->add($lines, $purchaseLines[$category], $total['taxable'], 0, $total['vat']);
            $purchaseVat += $total['vat'];
        }
        $this->add($lines, 23, 0, 0, round($purchaseVat, 2));

        // ── Part 5 (increasing): none in this draft (line 28 total only)
        $this->add($lines, 28, 0, 0, 0);

        // ── Part 6 (decreasing): sales credit notes → line 32; purchase credit notes → line 33
        $this->add($lines, 32, 0, 0, $salesCredit['vat']);
        $this->add($lines, 33, 0, 0, $purchaseCredit['vat']);
        $this->add($lines, 34, 0, 0, round($salesCredit['vat'] + $purchaseCredit['vat'], 2));

        // ── Part 7 net: 35 = 9 − 23 + 28 − 34, exactly as printed on the form
        $line9 = $lines[9]['vat'] ?? 0.0;
        $line23 = $lines[23]['vat'] ?? 0.0;
        $line28 = $lines[28]['vat'] ?? 0.0;
        $line34 = $lines[34]['vat'] ?? 0.0;
        $net = round($line9 - $line23 + $line28 - $line34, 2);
        $this->add($lines, 35, 0, 0, $net);

        ksort($lines);

        $result = [
            'lines'    => $lines,
            'net'      => $net,
            'unmapped' => $unmapped, // categories with no 9.1 line: must be resolved before filing
        ];

        // Never final while any blocker is open. Only the gate decides the status.
        $blockers = (new VatFilingGate())->blockers($from, $to, $reports, $result);
        $result['blockers'] = $blockers;
        $result['status'] = $blockers === [] ? 'final' : 'draft';
        $result['draft'] = $result['status'] !== 'final';

        return $result;
    }

    private function add(array &$lines, int $line, float $value, float $sd, float $vat): void
    {
        $lines[$line] ??= ['value' => 0.0, 'sd' => 0.0, 'vat' => 0.0];
        $lines[$line]['value'] = round($lines[$line]['value'] + $value, 2);
        $lines[$line]['sd'] = round($lines[$line]['sd'] + $sd, 2);
        $lines[$line]['vat'] = round($lines[$line]['vat'] + $vat, 2);
    }

    private function sumValue(array $lines): float
    {
        return round(array_sum(array_map(fn ($l) => $l['value'], array_filter(
            $lines,
            fn ($k) => $k >= 1 && $k <= 8,
            ARRAY_FILTER_USE_KEY
        ))), 2);
    }
}
