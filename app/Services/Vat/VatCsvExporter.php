<?php

namespace App\Services\Vat;

/**
 * CSV export of the Mushak working registers.
 *
 * The column layout lives in COLUMNS, in one place. These are working columns built
 * from the fields the registers hold. They are NOT the official NBR template: once the
 * official column order is confirmed, change COLUMNS and nothing else.
 */
class VatCsvExporter
{
    public const COLUMNS = [
        'sales' => [
            'Invoice No'     => 'invoice',
            'Invoice Date'   => 'date',
            'Buyer BIN'      => 'buyer_bin',
            'Taxable Value'  => 'taxable',
            'Output VAT'     => 'vat',
        ],
        'purchase' => [
            'Invoice No'     => 'invoice',
            'Invoice Date'   => 'date',
            'Supplier Name'  => 'supplier_name',
            'Supplier BIN'   => 'supplier_bin',
            'Taxable Value'  => 'taxable',
            'Input VAT'      => 'vat',
        ],
    ];

    /**
     * @param  array<int, array<string, mixed>>  $rows  rows from VatReportService
     */
    public function toCsv(string $type, array $rows): string
    {
        if (!isset(self::COLUMNS[$type])) {
            throw new \InvalidArgumentException("Unknown register: {$type}");
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys(self::COLUMNS[$type]));

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($key) => $row[$key] ?? '', array_values(self::COLUMNS[$type])));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
