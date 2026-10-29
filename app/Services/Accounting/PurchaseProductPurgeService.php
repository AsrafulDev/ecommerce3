<?php

namespace App\Services\Accounting;

use App\Models\FinancialTransactionPurgeLog;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Services\PricingService;
use App\Services\SupplierPaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin purge of a purchase or a product. Each one is refused while anything that
 * depends on it still exists: a sale, a payment, a return, a warranty, a VAT credit note,
 * or a sold or adjusted stock batch. A refused record must be reversed first.
 *
 * A purchase's journal is removed by the package's administrative purge, which records the
 * snapshot in the purge log. Stock is recalculated from the batches that remain.
 */
class PurchaseProductPurgeService
{
    public const SUPPORTED = ['purchase', 'product'];

    /** Product tables that belong to the product itself. They are removed with it. */
    private const PRODUCT_OWNED = [
        'productcolors', 'productimages', 'productsizes', 'product_variant_prices',
        'product_wholesale_prices', 'product_warranty_tiers',
    ];

    /** Batch tables that hang off a stock batch, with the column that points at the batch. */
    private const BATCH_CHILDREN = [
        'batch_wholesale_prices' => 'stock_batch_id',
        'batch_variant_prices' => 'stock_batch_id',
        'batch_warranty_tiers' => 'stock_batch_id',
        'batch_sn_lists' => 'batch_id',
    ];

    public function __construct(
        private AdvancedAccountingGateway $accounting,
        private PricingService $pricing,
        private SupplierPaymentReconciliationService $supplierReconciliation,
    ) {}

    /**
     * @return array{type: string, id: int, record: ?object, dependencies: array, blockers: array<int, string>,
     *               eligible: bool, effective_date: ?Carbon, age_days: ?int, days_remaining: int, window_days: ?int}
     */
    public function inspect(string $type, int $id): array
    {
        return match ($type) {
            'purchase' => $this->inspectPurchase($id),
            'product' => $this->inspectProduct($id),
            default => throw new \DomainException('Stock purge does not support this record type.'),
        };
    }

    /** The purchases and products shown on the transaction control page. */
    public function listRows(): \Illuminate\Support\Collection
    {
        $purchases = Purchase::query()->latest('id')->limit(25)->get()
            ->map(fn (Purchase $purchase) => $this->row('purchase', $purchase->id));

        $products = Product::query()->latest('id')->limit(25)->get()
            ->map(fn (Product $product) => $this->row('product', $product->id));

        return $purchases->concat($products);
    }

    public function purge(string $type, int $id, Authenticatable $actor, string $reason): FinancialTransactionPurgeLog
    {
        return match ($type) {
            'purchase' => $this->purgePurchase($id, $actor, $reason),
            'product' => $this->purgeProduct($id, $actor, $reason),
            default => throw new \DomainException('Stock purge does not support this record type.'),
        };
    }

    // ── Purchase ─────────────────────────────────────────────

    private function inspectPurchase(int $id): array
    {
        $purchase = DB::table('purchases')->where('id', $id)->first();
        if (! $purchase) {
            return $this->result('purchase', $id, null, [], ['PURCHASE_NOT_FOUND']);
        }

        $items = DB::table('purchase_items')->where('purchase_id', $id)->pluck('id');
        $batchIds = DB::table('stock_batches')->where('purchase_id', $id)->pluck('id');

        $blockers = [];
        $dependencies = [
            ['type' => 'purchase_items', 'count' => $items->count()],
            ['type' => 'stock_batches', 'count' => $batchIds->count()],
        ];

        if (DB::table('supplier_payments')->where('purchase_id', $id)->exists()) {
            $blockers[] = 'SUPPLIER_PAYMENTS_EXIST';
        }
        if (DB::table('purchase_items')->where('purchase_id', $id)->where('returned_qty', '>', 0)->exists()) {
            $blockers[] = 'PURCHASE_HAS_RETURNS';
        }
        if (DB::table('stock_batches')->where('purchase_id', $id)->whereColumn('remaining_qty', '!=', 'quantity')->exists()) {
            $blockers[] = 'BATCH_SOLD_OR_ADJUSTED';
        }
        if (DB::table('warranty_sales')->where('purchase_id', $id)->orWhereIn('stock_batch_id', $batchIds)->exists()) {
            $blockers[] = 'WARRANTY_SALE_EXISTS';
        }
        if ($this->tableHas('supplier_returns', fn ($q) => $q->where('purchase_id', $id))) {
            $blockers[] = 'SUPPLIER_RETURN_EXISTS';
        }
        if ($this->tableHas('supplier_warranties', fn ($q) => $q->whereIn('purchase_item_id', $items))) {
            $blockers[] = 'SUPPLIER_WARRANTY_EXISTS';
        }
        if ($this->tableHas('vat_credit_notes', fn ($q) => $q->where('purchase_id', $id))) {
            $blockers[] = 'VAT_CREDIT_NOTE_EXISTS';
        }

        $journal = $this->accounting->financialJournalState('purchase', $id);
        if (is_array($journal) && isset($journal['blocker'])) {
            $blockers[] = $journal['blocker'];
        }
        if (is_array($journal) && ! isset($journal['blocker'])) {
            $dependencies[] = ['type' => 'advanced_journal', 'snapshot' => $journal];
        }

        $effective = Carbon::parse($purchase->purchase_date);

        return $this->result('purchase', $id, $purchase, $dependencies, $blockers, $effective);
    }

    private function purgePurchase(int $id, Authenticatable $actor, string $reason): FinancialTransactionPurgeLog
    {
        return DB::transaction(function () use ($id, $actor, $reason) {
            $purchase = Purchase::whereKey($id)->lockForUpdate()->first();
            if (! $purchase) {
                throw new \DomainException('The purchase no longer exists.');
            }

            $inspection = $this->inspectPurchase($id);
            if (! $inspection['eligible']) {
                throw new \DomainException('Purge blocked: '.implode(', ', $inspection['blockers']));
            }

            $items = DB::table('purchase_items')->where('purchase_id', $id)->get();
            $batches = DB::table('stock_batches')->where('purchase_id', $id)->get();
            $batchIds = $batches->pluck('id');
            $productIds = $batches->pluck('product_id')->unique()->values();
            $before = [
                'purchase' => (array) $purchase->getAttributes(),
                'items' => $items->map(fn ($row) => (array) $row)->all(),
                'batches' => $batches->map(fn ($row) => (array) $row)->all(),
            ];

            $journal = $this->accounting->purgeFinancialJournal('purchase', $id);
            $before['journal'] = $journal;

            foreach (self::BATCH_CHILDREN as $table => $column) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereIn($column, $batchIds)->delete();
                }
            }
            DB::table('stock_batches')->where('purchase_id', $id)->delete();

            DB::table('purchase_item_prices')->whereIn('purchase_item_id', $items->pluck('id'))->delete();
            DB::table('purchase_items')->where('purchase_id', $id)->delete();
            if (Schema::hasTable('purchase_logs')) {
                DB::table('purchase_logs')->where('purchase_id', $id)->delete();
            }
            DB::table('purchases')->where('id', $id)->delete();

            // The supplier's stored due is worked out from its purchases, so it follows the deletion.
            if ($purchase->supplier_id && ($supplier = Supplier::find($purchase->supplier_id))) {
                $this->supplierReconciliation->reconcileSupplier($supplier);
            }

            $this->recalculateStock($productIds);

            return $this->log(
                'purchase',
                $id,
                $purchase->invoice_no ?: 'PURCHASE #'.$id,
                Carbon::parse($purchase->purchase_date),
                (float) $purchase->grand_total,
                $inspection['dependencies'],
                $before,
                $reason,
                $actor,
                ['window_days' => $inspection['window_days']],
            );
        });
    }

    // ── Product ──────────────────────────────────────────────

    private function inspectProduct(int $id): array
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (! $product) {
            return $this->result('product', $id, null, [], ['PRODUCT_NOT_FOUND']);
        }

        $blockers = [];
        $dependencies = [];

        foreach ($this->tablesWithProductId() as $table) {
            if (in_array($table, self::PRODUCT_OWNED, true)) {
                continue;
            }
            $count = DB::table($table)->where('product_id', $id)->count();
            if ($count > 0) {
                $blockers[] = 'PRODUCT_USED_IN_'.strtoupper($table);
                $dependencies[] = ['type' => $table, 'count' => $count];
            }
        }

        return $this->result('product', $id, $product, $dependencies, $blockers);
    }

    private function purgeProduct(int $id, Authenticatable $actor, string $reason): FinancialTransactionPurgeLog
    {
        return DB::transaction(function () use ($id, $actor, $reason) {
            $product = Product::whereKey($id)->lockForUpdate()->first();
            if (! $product) {
                throw new \DomainException('The product no longer exists.');
            }

            $inspection = $this->inspectProduct($id);
            if (! $inspection['eligible']) {
                throw new \DomainException('Purge blocked: '.implode(', ', $inspection['blockers']));
            }

            $before = ['product' => (array) $product->getAttributes(), 'owned' => []];
            foreach (self::PRODUCT_OWNED as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                    $rows = DB::table($table)->where('product_id', $id)->get();
                    $before['owned'][$table] = $rows->map(fn ($row) => (array) $row)->all();
                    DB::table($table)->where('product_id', $id)->delete();
                }
            }

            DB::table('products')->where('id', $id)->delete();
            Cache::forget('product_details_'.$product->slug);

            return $this->log(
                'product',
                $id,
                $product->name,
                null,
                (float) $product->new_price,
                $inspection['dependencies'],
                $before,
                $reason,
                $actor,
                [],
            );
        });
    }

    // ── Shared ───────────────────────────────────────────────

    /** Sets products.stock from the batches that remain, then refreshes the website cache. */
    private function recalculateStock(\Illuminate\Support\Collection $productIds): void
    {
        foreach ($productIds as $productId) {
            $sum = (int) DB::table('stock_batches')->where('product_id', $productId)->where('remaining_qty', '>', 0)->sum('remaining_qty');
            DB::table('products')->where('id', $productId)->update(['stock' => $sum]);

            if (config('pricing.batch_wise', false) && ($product = Product::find($productId))) {
                $this->pricing->refreshProductCache($product);
            }
        }
    }

    private function tablesWithProductId(): array
    {
        return collect(DB::select(
            'SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ? AND TABLE_NAME <> ?',
            ['product_id', 'products']
        ))->pluck('t')->all();
    }

    private function tableHas(string $table, \Closure $scope): bool
    {
        return Schema::hasTable($table) && DB::table($table)->where($scope)->exists();
    }

    private function row(string $type, int $id): array
    {
        $inspection = $this->inspect($type, $id);
        $record = $inspection['record'];

        return [
            'type' => $type,
            'id' => $id,
            'reference' => $type === 'purchase'
                ? ($record->invoice_no ?: 'Purchase #'.$id)
                : ($record->name ?? 'Product #'.$id),
            'amount' => $type === 'purchase' ? (float) ($record->grand_total ?? 0) : (float) ($record->new_price ?? 0),
            'effective_date' => $inspection['effective_date'],
            'age_days' => $inspection['age_days'],
            'days_remaining' => $inspection['days_remaining'],
            'blockers' => $inspection['blockers'],
            'eligible' => $inspection['eligible'],
            'purge_type' => $type,
            'expected_confirmation' => 'DELETE '.strtoupper($type).'-'.$id,
        ];
    }

    private function result(string $type, int $id, ?object $record, array $dependencies, array $blockers, ?Carbon $effective = null): array
    {
        $window = max(0, (int) config('accounting.transaction_hard_delete_window_days', 30));
        $expired = $effective !== null && $effective->lt(now()->copy()->subDays($window));
        if ($effective !== null && $expired) {
            $blockers[] = 'WINDOW_EXPIRED';
        }

        $blockers = array_values(array_unique($blockers));
        $found = $record !== null;

        return [
            'type' => $type,
            'id' => $id,
            'record' => $record,
            'dependencies' => $dependencies,
            'blockers' => $blockers,
            'eligible' => $found && $blockers === [],
            'effective_date' => $effective,
            'age_days' => $effective?->diffInDays(now()),
            'days_remaining' => $effective !== null && ! $expired ? max(0, now()->diffInDays($effective->copy()->addDays($window), false)) : 0,
            'window_days' => $effective !== null ? $window : null,
        ];
    }

    private function log(string $type, int $id, string $reference, ?Carbon $effective, float $amount, array $dependencies, array $before, string $reason, Authenticatable $actor, array $metadata): FinancialTransactionPurgeLog
    {
        return FinancialTransactionPurgeLog::create([
            'transaction_type' => $type,
            'transaction_reference' => $reference,
            'source_type' => $type,
            'source_id' => $id,
            'effective_date' => $effective,
            'amount' => $amount,
            'performed_by' => $actor->getAuthIdentifier(),
            'performed_at' => now(),
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'dependency_snapshot' => $dependencies,
            'before_snapshot' => $before,
            'metadata' => $metadata,
        ]);
    }
}
