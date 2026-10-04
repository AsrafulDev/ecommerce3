<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\SupplierPayment;
use Carbon\CarbonInterface;

class TransactionEffectiveDateResolver
{
    public function resolve(object $record): ?CarbonInterface
    {
        return match (true) {
            $record instanceof Expense => $record->expense_date
                ? now()->parse($record->expense_date)->startOfDay() : $record->created_at,
            $record instanceof SupplierPayment => $record->payment_date
                ? now()->parse($record->payment_date)->startOfDay() : $record->created_at,
            $record instanceof Refund => $record->processed_at ?: $record->created_at,
            $record instanceof Purchase => $record->purchase_date
                ? now()->parse($record->purchase_date)->startOfDay() : $record->created_at,
            $record instanceof OrderPayment, $record instanceof FundTransaction => $record->created_at,
            default => $record->created_at ?? null,
        };
    }
}
