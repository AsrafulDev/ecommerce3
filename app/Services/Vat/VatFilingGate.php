<?php

namespace App\Services\Vat;

use App\Models\GeneralSetting;
use App\Models\Product;
use Carbon\Carbon;

/**
 * Decides whether a Mushak 9.1 working figure may be called FINAL. It is never final
 * while a business decision, a practitioner sign-off, or a data gap is open. Each
 * blocker is a plain sentence the owner can act on.
 */
class VatFilingGate
{
    /**
     * @param  array<string, mixed>  $draft  output of Mushak91Draft (unmapped categories)
     * @return array<int, string>  empty when the return may be final
     */
    public function blockers(Carbon $from, Carbon $to, VatReportService $reports, array $draft = []): array
    {
        $blockers = [];
        $settings = GeneralSetting::orderBy('id', 'desc')->first();

        if (!$settings || !$settings->vat_enabled) {
            $blockers[] = 'VAT is switched off in settings (A1: registration not confirmed).';
        }
        if ($settings && $settings->vat_prices_include_vat === null) {
            $blockers[] = 'The price basis is not chosen: VAT-inclusive or exclusive (A3).';
        }
        if (!$settings || blank($settings->vat_bin)) {
            $blockers[] = 'The VAT registration number (BIN) is missing (A1).';
        }
        if (config('vat.filing_frequency') === null) {
            $blockers[] = 'The filing frequency is not set: monthly or quarterly (A5).';
        }
        if (!config('vat.mapping_approved')) {
            $blockers[] = 'The category-to-line mapping has not been approved by a qualified practitioner (B1).';
        }

        $withoutCategory = Product::whereNull('vat_category')->whereNotNull('vat_rate')->count();
        if ($withoutCategory > 0) {
            $blockers[] = "{$withoutCategory} product(s) have a VAT rate but no VAT category (A2).";
        }
        if (!empty($draft['unmapped'])) {
            $blockers[] = 'Some sales or purchases carry a category with no 9.1 line. They are excluded until mapped.';
        }

        $month = $reports->monthReturn($from, $to);
        $missing = $month['sales_lines_without_vat'] + $month['purchase_lines_without_vat'];
        if ($missing > 0) {
            $blockers[] = "{$missing} line(s) in this period have no VAT recorded.";
        }

        if ($reports->creditNoteTotals('sale', $from, $to)['vat'] != 0.0
            || $reports->creditNoteTotals('purchase', $from, $to)['vat'] != 0.0) {
            $blockers[] = 'Credit notes in this period are not yet split by category. Lines 32 and 33 use totals only.';
        }

        return $blockers;
    }
}
