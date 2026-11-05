<?php

namespace App\Services\TransactionReset;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Models\StockBatch;
use App\Models\TransactionResetAudit;
use App\Models\User;
use App\Models\WarrantySale;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\DB;

/**
 * Reverses a sale through the order's own rules, and deletes a cancelled sale only when nothing ties it to the books.
 *
 * Nothing here forces a status. A sale is cancelled with the normal transition, or, when it has already been
 * completed or delivered, it goes through the normal return steps. Every change runs in one database transaction
 * with its audit row: if the books, the stock or the audit cannot be verified or written, everything rolls back.
 */
class SaleResetService
{
    public function __construct(
        private SaleReversalEligibility $eligibility,
        private SaleAccountingVerifier $books,
    ) {
    }

    /** Why a sale cannot be completely deleted. Empty when it can. */
    public function completeDeleteBlockers(Order $order): array
    {
        return $this->eligibility->deletionBlockers($order);
    }

    /**
     * Reverse the sale. Returns what was done and what the books show. Throws \DomainException, with the reasons,
     * when it is not allowed or when it cannot be verified.
     *
     * @return array{mode: string, previous_status: string, final_status: string, units: int, accounting: array, restocked: list<array<string, mixed>>, serials: array<string, int>}
     */
    public function reverse(Order $order, User $actor, string $reason, ?string $ip = null): array
    {
        return DB::transaction(function () use ($order, $actor, $reason, $ip) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $previous = (string) $order->order_status;
            $inspection = $this->eligibility->inspect($order);

            if ($inspection['mode'] === null) {
                throw new \DomainException(implode(' ', $inspection['blockers']));
            }

            // The sale exactly as it was before anything changed, kept with the audit row.
            $snapshot = $this->snapshot($order);
            $journalsBefore = $this->books->postedSaleJournals($order);

            if ($inspection['mode'] === SaleReversalEligibility::MODE_CANCEL) {
                if (! $order->cancel($reason, $actor->getAuthIdentifier())) {
                    throw new \DomainException('This sale cannot be cancelled from its current status.');
                }
                app(OrderStatusService::class)->handleStatusChange($order, $previous, OrderStatus::CANCELLED->value, true);
                $final = OrderStatus::CANCELLED->value;
            } else {
                // Completed or delivered: the same steps the return screen takes. Stock and VAT are reversed once,
                // at the step into "returned", by the status service.
                foreach ([OrderStatus::RETURN_REQUESTED, OrderStatus::RETURN_APPROVED, OrderStatus::RETURNED] as $step) {
                    if (! $order->transitionTo($step, $reason, $actor->getAuthIdentifier())) {
                        throw new \DomainException("The sale could not move to \"{$step->value}\".");
                    }
                    $order = $order->fresh();
                    if ($step === OrderStatus::RETURNED) {
                        app(OrderStatusService::class)->handleStatusChange($order, 'return_approved', OrderStatus::RETURNED->value, true);
                    }
                }
                $final = OrderStatus::RETURNED->value;
            }

            // The books must show the reversal. Checked after the change, inside the same transaction.
            $accounting = $this->books->verifyReversed($order, $journalsBefore);
            $summary = $this->reversalSummary($order);

            $this->audit(
                $order,
                $inspection['mode'] === SaleReversalEligibility::MODE_CANCEL ? 'cancel' : 'return',
                'reverse',
                $previous,
                $final,
                $reason,
                $actor,
                $ip,
                $snapshot,
                $accounting,
                $summary,
            );

            return [
                'mode' => $inspection['mode'],
                'previous_status' => $previous,
                'final_status' => $final,
                'units' => $inspection['units'],
                'accounting' => $accounting,
                'restocked' => $summary['restocked'],
                'serials' => $summary['serials'],
            ];
        });
    }

    /**
     * Remove a cancelled sale completely. Refused, with the reasons, while anything still refers to it.
     * The full record is saved in the audit row before anything is removed.
     */
    public function completeDelete(Order $order, User $actor, string $reason, ?string $ip = null): void
    {
        DB::transaction(function () use ($order, $actor, $reason, $ip) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $blockers = $this->eligibility->deletionBlockers($order);
            if ($blockers !== []) {
                throw new \DomainException(implode(' ', $blockers));
            }

            $this->audit($order, 'complete_delete', 'delete', (string) $order->order_status, null, $reason, $actor, $ip, $this->snapshot($order), null, null);

            Shipping::where('order_id', $order->id)->delete();
            OrderDetails::where('order_id', $order->id)->delete();
            DB::table('order_notes')->where('order_id', $order->id)->delete();
            $order->delete();
        });
    }

    /** The sale as it stands: the order, its lines, its delivery details, its warranty records and its buyer. */
    private function snapshot(Order $order): array
    {
        return [
            'order' => $order->toArray(),
            'lines' => OrderDetails::where('order_id', $order->id)->get()->toArray(),
            'shipping' => Shipping::where('order_id', $order->id)->get()->toArray(),
            'warranty_sales' => WarrantySale::where('order_id', $order->id)->get()->toArray(),
            'buyer' => optional(Customer::find($order->customer_id))->only(['id', 'name', 'phone']),
        ];
    }

    /**
     * What the reversal did, read back from the records it wrote: the stock batches it restocked, and where each
     * serial number now sits.
     *
     * @return array{restocked: list<array<string, mixed>>, serials: array<string, int>}
     */
    private function reversalSummary(Order $order): array
    {
        $restocked = StockBatch::query()
            ->where('reference_type', 'sale_return')
            ->where('reference_id', $order->id)
            ->get(['id', 'product_id', 'variant_price_id', 'quantity', 'unit_cost'])
            ->map(fn ($batch) => [
                'batch_id' => $batch->id,
                'product_id' => $batch->product_id,
                'variant_price_id' => $batch->variant_price_id,
                'quantity' => (int) $batch->quantity,
                'unit_cost' => (float) $batch->unit_cost,
            ])
            ->values()
            ->all();

        $serials = [];
        foreach (WarrantySale::where('order_id', $order->id)->get() as $warranty) {
            foreach ((array) $warranty->serial_numbers as $serial) {
                $home = StockBatch::query()
                    ->where('product_id', $warranty->product_id)
                    ->get(['id', 'sn_stock'])
                    ->first(fn ($batch) => in_array($serial, (array) $batch->sn_stock, true));
                if ($home) {
                    $serials[(string) $serial] = (int) $home->id;
                }
            }
        }

        return ['restocked' => $restocked, 'serials' => $serials];
    }

    /** Writes the audit row. Insert only; this service never changes or removes one. */
    private function audit(Order $order, string $mode, string $action, string $previous, ?string $final, string $reason, User $actor, ?string $ip, ?array $snapshot, ?array $accounting, ?array $summary): void
    {
        TransactionResetAudit::create([
            'action' => $action,
            'mode' => $mode,
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'previous_status' => $previous,
            'new_status' => $final,
            'reason' => $reason,
            'performed_by' => $actor->getAuthIdentifier(),
            'performed_by_name' => $actor->name,
            'ip' => $ip,
            'snapshot' => $snapshot,
            'accounting_outcome' => $accounting['status'] ?? null,
            'reversal_summary' => $accounting === null ? null : ['accounting' => $accounting, 'summary' => $summary],
            'created_at' => now(),
        ]);

        log_activity('Sales', $action === 'delete' ? 'complete_delete' : 'reset_'.$mode, "Sale {$order->invoice_id}: {$reason}", null, [
            'order_id' => $order->id,
            'previous_status' => $previous,
            'new_status' => $final,
            'accounting' => $accounting['status'] ?? null,
        ]);
    }
}
