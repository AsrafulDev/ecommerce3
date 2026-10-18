<?php

namespace App\Services\Registers;

use App\Enums\TransactionCategory;
use App\Helpers\FundHelper;
use App\Models\FixedAsset;
use App\Models\FixedAssetEvent;
use App\Models\FundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fixed assets: purchase and disposal move cash. Depreciation and revaluation are
 * recorded on the asset's own history and move no cash.
 */
class FixedAssetService
{
    public function purchase(array $data, ?int $userId): FixedAsset
    {
        $cost = round((float) $data['cost'], 2);

        // Balance check and write run under the till lock (see FundHelper::debitIfCovered).
        $asset = FundHelper::debitIfCovered($cost, fn () => DB::transaction(function () use ($data, $cost, $userId) {
            $asset = FixedAsset::create([
                'name' => $data['name'],
                'purchase_date' => $data['purchase_date'],
                'cost' => $cost,
                'created_by' => $userId,
            ]);

            $fund = FundTransaction::create([
                'direction' => 'out',
                'source' => 'fixed_asset',
                'source_id' => $asset->id,
                'transaction_category' => TransactionCategory::ASSET_PURCHASE,
                'amount' => $cost,
                'note' => 'Fixed asset bought: '.$asset->name,
                'created_by' => $userId ?? 1,
            ]);
            $asset->update(['purchase_fund_transaction_id' => $fund->id]);

            return $asset;
        }));

        if ($asset === null) {
            throw new RuntimeException('Not enough balance in fund to buy this asset.');
        }

        app(\App\Services\Accounting\AdvancedAccountingGateway::class)->recordFixedAsset($asset);

        return $asset;
    }

    public function depreciate(FixedAsset $asset, float $amount, string $date, ?string $note, ?int $userId): FixedAssetEvent
    {
        $this->assertActive($asset);
        $amount = round($amount, 2);

        if ($amount <= 0 || $amount > $asset->bookValue()) {
            throw new RuntimeException('Depreciation must be more than 0 and no more than the book value.');
        }

        $event = DB::transaction(function () use ($asset, $amount, $date, $note, $userId) {
            $asset->update(['accumulated_depreciation' => round((float) $asset->accumulated_depreciation + $amount, 2)]);

            return FixedAssetEvent::create([
                'fixed_asset_id' => $asset->id, 'type' => 'depreciation', 'amount' => $amount,
                'event_date' => $date, 'note' => $note, 'created_by' => $userId,
            ]);
        });

        app(\App\Services\Accounting\AdvancedAccountingGateway::class)->recordDepreciation($event);

        return $event;
    }

    public function revalue(FixedAsset $asset, float $newValue, string $date, ?string $note, ?int $userId): FixedAssetEvent
    {
        $this->assertActive($asset);
        $newValue = round($newValue, 2);

        if ($newValue < 0) {
            throw new RuntimeException('A revalued amount cannot be negative.');
        }

        return DB::transaction(function () use ($asset, $newValue, $date, $note, $userId) {
            $asset->update(['revalued_amount' => $newValue]);

            return FixedAssetEvent::create([
                'fixed_asset_id' => $asset->id, 'type' => 'revaluation', 'amount' => $newValue,
                'event_date' => $date, 'note' => $note, 'created_by' => $userId,
            ]);
        });
    }

    /**
     * Sell or scrap the asset. Returns the gain (positive) or loss (negative) against
     * its book value at the time of disposal.
     */
    public function dispose(FixedAsset $asset, float $value, string $date, string $reason, ?int $userId): float
    {
        $this->assertActive($asset);
        $value = round($value, 2);

        if ($value < 0) {
            throw new RuntimeException('A disposal value cannot be negative.');
        }

        $gain = round($value - $asset->bookValue(), 2);

        DB::transaction(function () use ($asset, $value, $date, $reason, $userId) {
            $fundId = null;
            if ($value > 0) {
                $fund = FundTransaction::create([
                    'direction' => 'in',
                    'source' => 'fixed_asset_disposal',
                    'source_id' => $asset->id,
                    'transaction_category' => TransactionCategory::ASSET_DISPOSAL,
                    'amount' => $value,
                    'note' => 'Fixed asset disposed: '.$asset->name.' — '.$reason,
                    'created_by' => $userId ?? 1,
                ]);
                $fundId = $fund->id;
            }

            $asset->update([
                'status' => FixedAsset::DISPOSED,
                'disposal_date' => $date,
                'disposal_value' => $value,
                'disposal_reason' => $reason,
                'disposal_fund_transaction_id' => $fundId,
            ]);
        });

        return $gain;
    }

    protected function assertActive(FixedAsset $asset): void
    {
        if ($asset->status === FixedAsset::DISPOSED) {
            throw new RuntimeException('This asset has been disposed of and cannot change.');
        }
    }
}
