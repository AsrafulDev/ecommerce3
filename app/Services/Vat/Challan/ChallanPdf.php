<?php

namespace App\Services\Vat\Challan;

use App\Models\VatChallan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Renders a challan's printed copy and reads the stored copy back. The stored copy is what the user gets, and it is
 * checked against the hash recorded at issue.
 */
class ChallanPdf
{
    public function render(VatChallan $challan): string
    {
        return Pdf::loadView('backEnd.vat.challans.pdf', ['challan' => $challan->loadMissing('lines')])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    /**
     * @throws \DomainException when the stored copy is missing or no longer matches the hash recorded at issue
     */
    public function stored(VatChallan $challan): string
    {
        if (! $challan->pdf_path || ! Storage::disk('local')->exists($challan->pdf_path)) {
            throw new \DomainException("The stored PDF for challan {$challan->challan_no} is missing.");
        }

        $bytes = Storage::disk('local')->get($challan->pdf_path);
        if (! hash_equals((string) $challan->pdf_sha256, hash('sha256', $bytes))) {
            throw new \DomainException("The stored PDF for challan {$challan->challan_no} does not match the hash recorded at issue.");
        }

        return $bytes;
    }
}
