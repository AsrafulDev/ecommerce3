<?php

namespace App\Services\TransactionReset;

use App\Helpers\FundHelper;
use App\Models\Complaint;
use App\Models\DigitalDownload;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\StockBatch;
use App\Models\VatCreditNote;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Support\Accounting\AccountingAvailability;

/**
 * Decides, before anything changes, how a sale can be reversed by an administrator, and lists every reason
 * it cannot. Nothing here changes data.
 *
 * Mode "cancel": the sale has not left the warehouse yet (pending to packed). It is cancelled with the normal
 * order transition, which restocks it at its original batch cost.
 *
 * Mode "return": the sale was completed or delivered. The order state machine does not allow a direct cancel,
 * so the sale goes through the normal return steps, and it ends as "returned", not "cancelled".
 *
 * Fail closed: a sale is blocked when its cash, courier, accounting, serial or stock records cannot be tied to it
 * with certainty, or when a later record (refund, complaint, warranty claim, download, VAT note) already refers to it.
 */
class SaleReversalEligibility
{
    public const MODE_CANCEL = 'cancel';

    public const MODE_RETURN = 'return';

    /** Statuses in which the sale still holds stock (see OrderStatus::consumesStock). */
    private const HOLDS_STOCK = ['confirmed', 'picking', 'packing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'return_requested', 'return_approved'];

    public function __construct(
        private AdvancedAccountingGateway $accounting,
        private SaleAccountingVerifier $books,
        private SerialOrigin $serials,
    ) {
    }

    /**
     * @return array{mode: ?string, blockers: list<string>, lines: int, units: int}
     */
    public function inspect(Order $order): array
    {
        $status = (string) $order->order_status;
        $blockers = [];
        $mode = match (true) {
            in_array($status, ['pending', 'confirmed', 'picking', 'packing', 'packed'], true) => self::MODE_CANCEL,
            in_array($status, ['completed', 'delivered'], true) => self::MODE_RETURN,
            default => null,
        };

        if ($mode === null) {
            $blockers[] = match ($status) {
                'cancelled' => 'Already cancelled.',
                'returned', 'closed' => 'Already returned: the return reversed its stock and VAT.',
                'shipped', 'out_for_delivery' => 'The parcel is with the courier. It can be reversed after delivery.',
                'return_requested', 'return_approved' => 'A return is already in progress.',
                default => "A sale in status \"{$status}\" cannot be reversed here.",
            };
        } else {
            $blockers = array_merge($blockers, $this->reversalBlockers($order, $mode));
        }

        $lines = OrderDetails::where('order_id', $order->id)->get();

        return [
            'mode' => $blockers === [] ? $mode : null,
            'blockers' => array_values(array_unique($blockers)),
            'lines' => $lines->count(),
            'units' => (int) $lines->sum('qty'),
        ];
    }

    /** @return list<string> */
    private function reversalBlockers(Order $order, string $mode): array
    {
        $blockers = [];

        if (Refund::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A refund is recorded for this sale; its stock may already be restored.';
        }
        if (VatCreditNote::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A VAT credit note already exists for this sale, so its VAT is already reversed.';
        }
        if (StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->exists()) {
            $blockers[] = 'Stock was already restored for this sale.';
        }
        if (WarrantyClaim::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A warranty claim exists for this sale.';
        }
        if (Complaint::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A complaint is recorded for this sale.';
        }
        if (DigitalDownload::where('order_id', $order->id)->exists()) {
            $blockers[] = 'Digital download links were issued for this sale.';
        }

        // Cash: the sale's money is in the cash ledger. The reset does not reverse that money, so it must not run.
        $credited = FundHelper::creditedFor((int) $order->id);
        if ($credited > 0) {
            $blockers[] = 'The cash ledger holds '.number_format($credited, 2).' for this sale. Reversing that cash is not automated, so this sale needs reconciliation first.';
        }

        // Courier: the courier's own status is only synced while an order is packed or shipped, and nothing confirms
        // what the courier did after that. Fail closed: a consignment blocks the reset in either mode.
        if (filled($order->courier_tracking_id) || $order->courier_sent_at !== null) {
            $blockers[] = "A courier consignment ({$order->courier_tracking_id}) is recorded for this sale. The courier's status cannot be verified from here, so the sale cannot be reversed.";
        }

        // Accounting: a posted journal must be reversed by the books, or the sale is not reversed at all.
        foreach ($this->books->blockers($order, $this->accounting) as $message) {
            $blockers[] = $message;
        }

        $holdsStock = in_array((string) $order->order_status, self::HOLDS_STOCK, true);
        foreach (OrderDetails::where('order_id', $order->id)->with('warrantySale')->get() as $line) {
            if (! $holdsStock || (int) $line->qty <= 0 || $line->product_id === null) {
                continue;
            }

            $allocation = $line->stockAllocation();
            if ($allocation === null) {
                $blockers[] = "\"{$line->product_name}\": the stock batches of this sale are not recorded, so its original cost cannot be restored.";
            } elseif (array_sum(array_column($allocation, 'qty')) !== (int) $line->qty) {
                $blockers[] = "\"{$line->product_name}\": the recorded batches do not add up to the quantity sold.";
            }

            $warranty = $line->warrantySale;
            $serials = is_array(optional($warranty)->serial_numbers) ? $warranty->serial_numbers : [];
            if ($serials !== []) {
                try {
                    $this->serials->resolve((int) $line->product_id, $serials, $warranty->stock_batch_id !== null ? (int) $warranty->stock_batch_id : null);
                } catch (\DomainException $e) {
                    $blockers[] = $e->getMessage();
                }
            }
        }

        return $blockers;
    }

    /** Money or records that tie a sale to the books. A sale with any of these cannot be completely deleted. */
    public function deletionBlockers(Order $order): array
    {
        $blockers = [];

        if ((string) $order->order_status !== 'cancelled') {
            $blockers[] = 'Cancel the sale first. Only a cancelled sale can be deleted completely.';
        }
        if (OrderPayment::where('order_id', $order->id)->exists() || Payment::where('order_id', $order->id)->exists()) {
            $blockers[] = 'Customer payments are recorded for this sale.';
        }
        if (FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists()) {
            $blockers[] = 'The cash ledger holds this sale.';
        }
        if (Refund::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A refund is recorded for this sale.';
        }
        if (VatCreditNote::where('order_id', $order->id)->exists()) {
            $blockers[] = 'VAT was declared for this sale; its credit note must be kept.';
        }
        if (WarrantySale::where('order_id', $order->id)->exists()) {
            $blockers[] = 'Warranty records are linked to this sale.';
        }
        if (WarrantyClaim::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A warranty claim exists for this sale.';
        }
        if (Complaint::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A complaint is recorded for this sale.';
        }
        if (DigitalDownload::where('order_id', $order->id)->exists()) {
            $blockers[] = 'Digital download links were issued for this sale.';
        }
        if (filled($order->courier_tracking_id) || $order->courier_sent_at !== null) {
            $blockers[] = "A courier consignment ({$order->courier_tracking_id}) is recorded for this sale.";
        }
        if (AccountingAvailability::enabled()) {
            $blockers[] = 'Advanced accounting keeps journal history for this sale.';
        }

        return $blockers;
    }
}
