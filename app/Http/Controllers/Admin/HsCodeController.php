<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Vat\HsCodeLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON search over the customs tariff, for the HS code field and its popup. Read-only.
 * Figures are returned as published. A fixed Taka duty is never offered as a VAT percentage.
 */
class HsCodeController extends Controller
{
    public function __construct(protected HsCodeLookup $lookup)
    {
        $this->middleware('permission:product-create|product-edit');
    }

    public function search(Request $request): JsonResponse
    {
        $query = mb_substr((string) $request->query('q', ''), 0, 100);
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 50);

        $page = $this->lookup->page($query, $offset, $limit);

        return response()->json([
            'loaded'  => $this->lookup->loaded(),
            'total'   => $page['total'],
            'offset'  => $page['offset'],
            'limit'   => $page['limit'],
            'results' => array_map(function ($row) {
                $special = (bool) ($row['specific_duty'] ?? false);

                return [
                    'code'          => $row['code'],
                    'description'   => $row['description'],
                    'cd'            => $row['cd'] ?? null,
                    'sd'            => $row['sd'] ?? null,
                    'vat'           => $special ? null : ($row['vat'] ?? null),
                    'ait'           => $row['ait'] ?? null,
                    'rd'            => $row['rd'] ?? null,
                    'at'            => $row['at'] ?? null,
                    'specific_duty' => $special,
                ];
            }, $page['items']),
        ]);
    }
}
