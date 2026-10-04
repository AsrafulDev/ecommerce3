<?php

namespace App\Services\Accounting;

use Carbon\Carbon;

class TransactionPurgeEligibilityService
{
    public function __construct(
        private TransactionEffectiveDateResolver $dates,
        private FinancialTransactionDependencyResolver $dependencies,
    ) {}

    public function inspect(string $type, int $id): array
    {
        $graph = $this->dependencies->resolve($type, $id);
        $record = $graph['record'];
        $effective = $record ? $this->dates->resolve($record) : null;
        $window = max(0, (int) config('accounting.transaction_hard_delete_window_days', 30));
        $expires = $effective?->copy()->addDays($window);
        $now = now();
        $expired = !$effective || $effective->lt($now->copy()->subDays($window));
        $blockers = $graph['blockers'];
        if ($expired) $blockers[] = 'WINDOW_EXPIRED';

        return [
            'type' => $type,
            'id' => $id,
            'record' => $record,
            'dependencies' => $graph['dependencies'],
            'blockers' => array_values(array_unique($blockers)),
            'effective_date' => $effective,
            'age_days' => $effective ? $effective->diffInDays($now) : null,
            'days_remaining' => $expires && !$expired ? max(0, $now->diffInDays($expires, false)) : 0,
            'window_days' => $window,
            'eligible' => $record && !$blockers,
        ];
    }
}
