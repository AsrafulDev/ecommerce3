<?php

namespace App\Services\Vat;

use Carbon\Carbon;

/**
 * The Mushak 6.1 (purchase book) and 6.2 (sales book) in the layout of the NBR forms: the same
 * columns, numbered as printed, under the same group headings.
 *
 * Only the columns this system tracks are filled. Opening, usage and closing balances of goods are
 * not kept by this app, so those columns stay blank rather than being guessed. The layout follows
 * the NBR form, but the mapping of each column is not yet reviewed (config 'vat.mapping_approved'):
 * until it is, the book is marked as a draft that is not approved for filing.
 */
final class MushakBook
{
    public function __construct(private readonly VatReportService $reports)
    {
    }

    /** @return array<string, mixed> */
    public function purchaseBook(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        $lines = $this->reports->purchaseLines($from, $to, $invoice);

        $columns = [
            ['key' => 'serial', 'no' => '১', 'label' => 'ক্রমিক সংখ্যা', 'group' => null],
            ['key' => 'date', 'no' => '২', 'label' => 'তারিখ', 'group' => null],
            ['key' => 'open_qty', 'no' => '৩', 'label' => 'পরিমাণ (একক)', 'group' => 'মজুদ উপকরণের প্রারম্ভিক জের'],
            ['key' => 'open_value', 'no' => '৪', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'মজুদ উপকরণের প্রারম্ভিক জের'],
            ['key' => 'invoice', 'no' => '৫', 'label' => 'চালানপত্র/বিল অব এন্ট্রি নম্বর', 'group' => null],
            ['key' => 'invoice_date', 'no' => '৬', 'label' => 'তারিখ', 'group' => null],
            ['key' => 'supplier_name', 'no' => '৭', 'label' => 'নাম', 'group' => 'বিক্রেতা/সরবরাহকারী'],
            ['key' => 'supplier_address', 'no' => '৮', 'label' => 'ঠিকানা', 'group' => 'বিক্রেতা/সরবরাহকারী'],
            ['key' => 'supplier_bin', 'no' => '৯', 'label' => 'নিবন্ধন/তালিকাভুক্তি/জাতীয় পরিচয়পত্র নং', 'group' => 'বিক্রেতা/সরবরাহকারী'],
            ['key' => 'product', 'no' => '১০', 'label' => 'বিবরণ', 'group' => null],
            ['key' => 'qty', 'no' => '১১', 'label' => 'পরিমাণ (একক)', 'group' => null],
            ['key' => 'taxable', 'no' => '১২', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => null],
            ['key' => 'sd', 'no' => '১৩', 'label' => 'সম্পূরক শুল্ক (যদি থাকে)', 'group' => null],
            ['key' => 'vat', 'no' => '১৪', 'label' => 'মূসক', 'group' => null],
            ['key' => 'total_qty', 'no' => '১৫', 'label' => 'পরিমাণ (একক)', 'group' => 'মোট উপকরণের পরিমাণ'],
            ['key' => 'total_value', 'no' => '১৬', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'মোট উপকরণের পরিমাণ'],
            ['key' => 'use_qty', 'no' => '১৭', 'label' => 'পরিমাণ (একক)', 'group' => 'পণ্য প্রস্তুত/প্রক্রিয়াকরণে উপকরণের ব্যবহার'],
            ['key' => 'use_value', 'no' => '১৮', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'পণ্য প্রস্তুত/প্রক্রিয়াকরণে উপকরণের ব্যবহার'],
            ['key' => 'close_qty', 'no' => '১৯', 'label' => 'পরিমাণ (একক)', 'group' => 'উপকরণের প্রান্তিক জের'],
            ['key' => 'close_value', 'no' => '২০', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'উপকরণের প্রান্তিক জের'],
            ['key' => 'remark', 'no' => '২১', 'label' => 'মন্তব্য', 'group' => null],
        ];

        $rows = [];
        foreach ($lines as $index => $line) {
            $rows[] = [
                'serial' => $index + 1,
                'date' => $line['date'],
                'invoice' => $line['invoice'],
                'invoice_date' => $line['date'],
                'supplier_name' => $line['supplier_name'],
                'supplier_address' => $line['supplier_address'],
                'supplier_bin' => $line['supplier_bin'],
                'product' => $line['product'],
                'qty' => $line['qty'],
                'taxable' => $line['taxable'],
                'sd' => null,
                'vat' => $line['vat'],
            ];
        }

        return $this->book(
            form: 'মূসক-৬.১',
            title: ['প্রতিষ্ঠানের নাম, ঠিকানা ও ব্যবসা সনাক্তকরণ সংখ্যা', 'ক্রয় হিসাব পুস্তক',
                '(পণ্য বা সেবা প্রক্রিয়াকরণে সম্পৃক্ত এমন নিবন্ধিত বা তালিকাভুক্ত ব্যক্তির জন্য প্রযোজ্য)',
                '[বিধি ৪০(১) এর দফা (ক) এবং ৪১ এর দফা (ক) দ্রষ্টব্য]'],
            section: 'পণ্য/সেবার উপকরণ ক্রয়',
            columns: $columns,
            rows: $rows,
            footnotes: [
                '১। অর্থনৈতিক কার্যক্রম সংশ্লিষ্ট সকল প্রকার ক্রয়ের তথ্য এই ফরমে অন্তর্ভুক্ত করিতে হইবে।',
                '২। যে ক্ষেত্রে অনিবন্ধিত ব্যক্তির নিকট হইতে পণ্য ক্রয় করা হইবে সেই ক্ষেত্রে উক্ত ব্যক্তির পূর্ণাঙ্গ নাম, ঠিকানা ও জাতীয় পরিচয়পত্র নম্বর যথাযথভাবে সংশ্লিষ্ট কলাম [(৭), (৮) ও (৯)] এ আবশ্যিকভাবে উল্লেখ করিতে হইবে।',
                '৩। উপকরণ ক্রয়ের স্বপক্ষে প্রামাণিক দলিল হিসাবে বিল অব এন্ট্রি বা চালানপত্রের কপি সংরক্ষণ করিতে হইবে।',
            ],
            invoice: $invoice,
            from: $from,
            to: $to,
        );
    }

    /** @return array<string, mixed> */
    public function salesBook(Carbon $from, Carbon $to, ?string $invoice = null): array
    {
        $lines = $this->reports->salesLines($from, $to, $invoice);

        $columns = [
            ['key' => 'serial', 'no' => '১', 'label' => 'ক্রমিক সংখ্যা', 'group' => null],
            ['key' => 'date', 'no' => '২', 'label' => 'তারিখ', 'group' => null],
            ['key' => 'produced_qty', 'no' => '৩', 'label' => 'পরিমাণ (একক)', 'group' => 'উৎপাদিত পণ্য/সেবার প্রারম্ভিক জের'],
            ['key' => 'produced_value', 'no' => '৪', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'উৎপাদিত পণ্য/সেবার প্রারম্ভিক জের'],
            ['key' => 'production_qty', 'no' => '৫', 'label' => 'পরিমাণ (একক)', 'group' => 'উৎপাদন'],
            ['key' => 'production_value', 'no' => '৬', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'উৎপাদন'],
            ['key' => 'total_produced_qty', 'no' => '৭', 'label' => 'পরিমাণ (একক)', 'group' => 'মোট উৎপাদিত পণ্য/সেবা'],
            ['key' => 'total_produced_value', 'no' => '৮', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'মোট উৎপাদিত পণ্য/সেবা'],
            ['key' => 'buyer_name', 'no' => '৯', 'label' => 'নাম', 'group' => 'ক্রেতা/সরবরাহগ্রহীতা'],
            ['key' => 'buyer_address', 'no' => '১০', 'label' => 'ঠিকানা', 'group' => 'ক্রেতা/সরবরাহগ্রহীতা'],
            ['key' => 'buyer_bin', 'no' => '১১', 'label' => 'নিবন্ধন/তালিকাভুক্তি/জাতীয় পরিচয়পত্র নং', 'group' => 'ক্রেতা/সরবরাহগ্রহীতা'],
            ['key' => 'invoice', 'no' => '১২', 'label' => 'নম্বর', 'group' => 'চালানপত্রের বিবরণ'],
            ['key' => 'invoice_date', 'no' => '১৩', 'label' => 'তারিখ', 'group' => 'চালানপত্রের বিবরণ'],
            ['key' => 'product', 'no' => '১৪', 'label' => 'বিবরণ', 'group' => 'বিক্রিত/সরবরাহকৃত পণ্যের বিবরণ'],
            ['key' => 'qty', 'no' => '১৫', 'label' => 'পরিমাণ (একক)', 'group' => 'বিক্রিত/সরবরাহকৃত পণ্যের বিবরণ'],
            ['key' => 'taxable', 'no' => '১৬', 'label' => 'করযোগ্য মূল্য', 'group' => 'বিক্রিত/সরবরাহকৃত পণ্যের বিবরণ'],
            ['key' => 'sd', 'no' => '১৭', 'label' => 'সম্পূরক শুল্ক (যদি থাকে)', 'group' => 'বিক্রিত/সরবরাহকৃত পণ্যের বিবরণ'],
            ['key' => 'vat', 'no' => '১৮', 'label' => 'মূসক', 'group' => 'বিক্রিত/সরবরাহকৃত পণ্যের বিবরণ'],
            ['key' => 'close_qty', 'no' => '১৯', 'label' => 'পরিমাণ (একক)', 'group' => 'পণ্যের প্রান্তিক জের'],
            ['key' => 'close_value', 'no' => '২০', 'label' => 'মূল্য (সকল প্রকার কর ব্যতীত)', 'group' => 'পণ্যের প্রান্তিক জের'],
            ['key' => 'remark', 'no' => '২২', 'label' => 'মন্তব্য', 'group' => null],
        ];

        $rows = [];
        foreach ($lines as $index => $line) {
            $rows[] = [
                'serial' => $index + 1,
                'date' => $line['date'],
                'buyer_name' => $line['buyer_name'],
                'buyer_address' => $line['buyer_address'],
                'buyer_bin' => $line['buyer_bin'],
                'invoice' => $line['invoice'],
                'invoice_date' => $line['date'],
                'product' => $line['product'],
                'qty' => $line['qty'],
                'taxable' => $line['taxable'],
                'sd' => null,
                'vat' => $line['vat'],
            ];
        }

        return $this->book(
            form: 'মূসক-৬.২',
            title: ['প্রতিষ্ঠানের নাম, ঠিকানা ও ব্যবসা সনাক্তকরণ সংখ্যা', 'বিক্রয় হিসাব পুস্তক',
                '(পণ্য বা সেবা প্রক্রিয়াকরণে সম্পৃক্ত এমন নিবন্ধিত বা তালিকাভুক্ত ব্যক্তির জন্য প্রযোজ্য)',
                '[ বিধি ৪০ এর উপ-বিধি (১) এর দফা (খ) এবং বিধি ৪১ এর দফা (ক) দ্রষ্টব্য ]'],
            section: 'পণ্য/সেবার বিক্রয়',
            columns: $columns,
            rows: $rows,
            footnotes: [
                '* যেইক্ষেত্রে অনিবন্ধিত ব্যক্তির নিকট পণ্য বিক্রয় করা হইবে সেইক্ষেত্রে উক্ত ব্যক্তির পূর্ণাঙ্গ নাম, ঠিকানা ও জাতীয় পরিচয়পত্র নম্বর যথাযথভাবে সংশ্লিষ্ট কলামে [(৯), (১০) ও (১১)] আবশ্যিকভাবে উল্লেখ করিতে হইবে।',
            ],
            invoice: $invoice,
            from: $from,
            to: $to,
        );
    }

    /**
     * @param  list<string>  $title
     * @param  list<array{key: string, no: string, label: string, group: ?string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $footnotes
     * @return array<string, mixed>
     */
    private function book(string $form, array $title, string $section, array $columns, array $rows, array $footnotes, ?string $invoice, Carbon $from, Carbon $to): array
    {
        $totals = [];
        foreach (['qty', 'taxable', 'sd', 'vat'] as $key) {
            $totals[$key] = round(array_sum(array_column($rows, $key)), 2);
        }

        return [
            'form' => $form,
            'title' => $title,
            'section' => $section,
            'footnotes' => $footnotes,
            'columns' => $columns,
            'head' => $this->head($columns),
            'rows' => $rows,
            'totals' => $totals,
            // Columns that hold an amount (right-aligned, two decimals) and the ones that are totalled.
            'amount_keys' => array_values(array_filter(array_column($columns, 'key'), fn ($key) => $key !== 'serial' && ! in_array($key, ['date', 'invoice', 'invoice_date', 'supplier_name', 'supplier_address', 'supplier_bin', 'buyer_name', 'buyer_address', 'buyer_bin', 'product', 'remark'], true))),
            'total_keys' => ['qty', 'taxable', 'sd', 'vat'],
            'period' => $invoice !== null ? 'Invoice '.$invoice : $from->toDateString().' to '.$to->toDateString(),
            'draft' => ! (bool) config('vat.mapping_approved'),
        ];
    }

    /**
     * Two header rows: a group row (ungrouped columns span both rows), then the column labels under their group.
     *
     * @param  list<array{key: string, no: string, label: string, group: ?string}>  $columns
     * @return array{groups: list<array{label: string, span: int, rowspan: int}>, leaves: list<array{label: string, no: string}>}
     */
    private function head(array $columns): array
    {
        $groups = [];
        foreach ($columns as $column) {
            if ($column['group'] === null) {
                $groups[] = ['label' => $column['label'], 'span' => 1, 'rowspan' => 2, 'no' => $column['no']];

                continue;
            }

            $last = end($groups);
            if ($last !== false && ($last['group'] ?? null) === $column['group']) {
                $groups[count($groups) - 1]['span']++;
            } else {
                $groups[] = ['label' => $column['group'], 'span' => 1, 'rowspan' => 1, 'group' => $column['group']];
            }
        }

        $leaves = [];
        foreach ($columns as $column) {
            if ($column['group'] !== null) {
                $leaves[] = ['label' => $column['label'], 'no' => $column['no']];
            }
        }

        return ['groups' => $groups, 'leaves' => $leaves];
    }
}
