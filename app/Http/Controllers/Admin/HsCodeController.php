<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Vat\HsCodeLookup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * JSON search over the customs tariff, for the HS code field on product forms.
 * Read-only. Returns the tariff figures as published; it never sets a product's VAT rate.
 */
class HsCodeController extends Controller
{
    public function __construct(protected HsCodeLookup $lookup)
    {
        $this->middleware('permission:product-create|product-edit');
    }

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'loaded'  => $this->lookup->loaded(),
            'results' => array_map(fn ($row) => [
                'code'        => $row['code'],
                'description' => $row['description'],
                // A fixed Taka amount is not a percentage, so no VAT percentage is offered for it.
                'vat'           => ($row['specific_duty'] ?? false) ? null : ($row['vat'] ?? null),
                'specific_duty' => (bool) ($row['specific_duty'] ?? false),
            ], $this->lookup->search(mb_substr($query, 0, 100))),
        ]);
    }
}
