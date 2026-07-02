<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;

    // সব ফিল্ড mass assign করতে পারবে
    protected $guarded = [];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getPaymentGatewayAttribute(): ?string
    {
        return $this->payment?->payment_method;
    }

    // ============================
    // 🌟 RELATIONSHIPS
    // ============================

    // অর্ডারের সব প্রোডাক্ট আইটেম (order_details টেবিল)
    public function orderdetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    // alias: items()
    public function items()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    // 🔥 অর্ডার থেকে সরাসরি Products আনতে (hasManyThrough)
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,      // শেষ মডেল (যেটা চাও)
            OrderDetails::class, // মধ্যবর্তী মডেল
            'order_id',          // order_details টেবিলের foreign key (order_id)
            'id',                // products টেবিলের primary key
            'id',                // orders টেবিলের local key
            'product_id'         // order_details টেবিলের foreign key (product_id)
        );
    }

    // পুরোনো কোডে যদি with('product') / $order->product থাকে,
    // সেটা ব্রেক না করার জন্য product() নামেও একই relation দিলাম।
    public function product()
    {
        return $this->products();
    }

    // পেমেন্ট স্ট্যাটাস / অর্ডার স্ট্যাটাস
    // Now uses slug-based matching since order_status stores enum string values
    public function status()
    {
        return $this->belongsTo(\App\Models\OrderStatus::class, 'order_status', 'slug');
    }

    // শিপিং তথ্য
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'order_id', 'id');
    }

    // পেমেন্ট ডাটা
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id', 'id');
    }

    // কাস্টমার (frontend user)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // অ্যাডমিন ইউজার (order created by)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============================
    // 🌟 DIGITAL DOWNLOAD SUPPORT
    // ============================

    // অর্ডার থেকে সব ডিজিটাল ডাউনলোড লিঙ্ক
    public function digitalDownloads()
    {
        return $this->hasMany(DigitalDownload::class, 'order_id');
    }

    // ============================
    // 🌟 REFUND SUPPORT
    // ============================

    // অর্ডারের সব রিফান্ড রিকোয়েস্ট
    public function refunds()
    {
        return $this->hasMany(Refund::class, 'order_id');
    }

    // অর্ডারের active/pending refund আছে কিনা
    public function hasPendingRefund()
    {
        return $this->refunds()->whereIn('status', ['pending', 'approved'])->exists();
    }

    // ═══════════════════════════════════════════════════════
    // 🌟 ORDER NOTES (History / Audit Trail)
    // ═══════════════════════════════════════════════════════

    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id')->latest();
    }

    /**
     * Add a note to this order.
     */
    public function addNote(
        string $content,
        string $type = 'info',
        string $source = 'admin',
        ?int $userId = null,
        ?array $metadata = null
    ): OrderNote {
        return OrderNote::addTo($this, $content, $type, $source, $userId, $metadata);
    }

    // ═══════════════════════════════════════════════════════
    // 🧭  ORDER STATUS (Enum-based, System-Driven)
    // ═══════════════════════════════════════════════════════

    /**
     * Get current order status as enum.
     */
    public function getStatusEnumAttribute(): OrderStatus
    {
        return OrderStatus::tryFrom($this->order_status) ?? OrderStatus::PENDING;
    }

    /**
     * Get current payment status as enum.
     */
    public function getPaymentStatusEnumAttribute(): PaymentStatus
    {
        return PaymentStatus::tryFrom($this->payment_status) ?? PaymentStatus::PENDING;
    }

    /**
     * Transition the order to a new status.
     * If direct transition isn't allowed, tries to auto-step through intermediates.
     */
    public function transitionTo(OrderStatus $newStatus, ?string $note = null, ?int $userId = null): bool
    {
        $current = $this->status_enum;

        if ($current === $newStatus) {
            return true;
        }

        // Direct transition allowed?
        if ($current->canTransitionTo($newStatus)) {
            return $this->executeTransition($current, $newStatus, $note, $userId);
        }

        // Try fast-forward (auto-step through intermediates)
        return $this->fastForwardTo($newStatus, $note, $userId);
    }

    /**
     * Execute a single direct transition (no auto-stepping).
     */
    private function executeTransition(OrderStatus $from, OrderStatus $to, ?string $note, ?int $userId): bool
    {
        $this->order_status = $to->value;
        $this->save();

        $meta = [
            'old_status' => $from->value,
            'new_status' => $to->value,
        ];
        $this->addNote(
            $note ?? "Status changed from {$from->label()} to {$to->label()}",
            'info',
            'system',
            $userId,
            $meta
        );

        Log::info('Order status transitioned', [
            'order_id'   => $this->id,
            'invoice_id' => $this->invoice_id,
            'from'       => $from->value,
            'to'         => $to->value,
            'user_id'    => $userId ?? auth()->id(),
        ]);

        return true;
    }

    // ═══════════════════════════════════════════════════════
    // 🎯  ACTION METHODS (called by controllers)
    // Each action auto-transitions + handles side effects
    // ═══════════════════════════════════════════════════════

    /**
     * Confirm the order (Pending → Confirmed).
     */
    public function confirm(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::CONFIRMED, $note ?? 'Order confirmed', $userId);
    }

    /**
     * Start picking (Confirmed → Picking).
     */
    public function startPicking(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::PICKING, $note ?? 'Picking started', $userId);
    }

    /**
     * Start packing (Picking → Packing).
     */
    public function startPacking(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::PACKING, $note ?? 'Packing started', $userId);
    }

    /**
     * Mark as packed (Packing → Packed).
     */
    public function markPacked(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::PACKED, $note ?? 'Order packed', $userId);
    }

    /**
     * Ship the order (Packed → Shipped).
     */
    public function ship(?string $courierType = null, ?string $trackingId = null, ?string $note = null, ?int $userId = null): bool
    {
        if ($courierType) {
            $this->courier_type = $courierType;
        }
        if ($trackingId) {
            $this->courier_tracking_id = $trackingId;
        }
        $this->courier_sent_at = now();
        $this->save();

        $meta = [
            'courier_type'    => $courierType,
            'tracking_id'     => $trackingId,
        ];

        return $this->transitionTo(OrderStatus::SHIPPED, $note ?? 'Order shipped', $userId);
    }

    /**
     * Mark out for delivery (Shipped → Out for Delivery).
     */
    public function markOutForDelivery(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::OUT_FOR_DELIVERY, $note ?? 'Out for delivery', $userId);
    }

    /**
     * Mark as delivered (Out for Delivery → Delivered).
     */
    public function markDelivered(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::DELIVERED, $note ?? 'Order delivered', $userId);
    }

    /**
     * Complete the order (Delivered → Completed).
     * Called when return window expires.
     */
    public function markCompleted(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::COMPLETED, $note ?? 'Order completed', $userId);
    }

    /**
     * Request a return (Delivered/Completed → Return Requested).
     */
    public function requestReturn(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::RETURN_REQUESTED, $note ?? 'Return requested', $userId);
    }

    /**
     * Approve a return (Return Requested → Return Approved).
     */
    public function approveReturn(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::RETURN_APPROVED, $note ?? 'Return approved', $userId);
    }

    /**
     * Mark item as returned (Return Approved → Returned).
     */
    public function markReturned(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::RETURNED, $note ?? 'Item returned', $userId);
    }

    /**
     * Close the order (Returned → Closed).
     */
    public function closeOrder(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(OrderStatus::CLOSED, $note ?? 'Order closed', $userId);
    }

    /**
     * Cancel the order. Only allowed for cancellable statuses.
     */
    public function cancel(?string $note = null, ?int $userId = null): bool
    {
        if (!$this->status_enum->isCancellable()) {
            Log::warning('Attempted to cancel non-cancellable order', [
                'order_id'   => $this->id,
                'status'     => $this->order_status,
            ]);
            return false;
        }
        return $this->transitionTo(OrderStatus::CANCELLED, $note ?? 'Order cancelled', $userId);
    }

    // ═══════════════════════════════════════════════════════
    // 🧪  STATUS HELPERS
    // ═══════════════════════════════════════════════════════

    public function isPending(): bool          { return $this->status_enum === OrderStatus::PENDING; }
    public function isConfirmed(): bool        { return $this->status_enum === OrderStatus::CONFIRMED; }
    public function isPicking(): bool          { return $this->status_enum === OrderStatus::PICKING; }
    public function isPacking(): bool          { return $this->status_enum === OrderStatus::PACKING; }
    public function isPacked(): bool           { return $this->status_enum === OrderStatus::PACKED; }
    public function isShipped(): bool          { return $this->status_enum === OrderStatus::SHIPPED; }
    public function isOutForDelivery(): bool   { return $this->status_enum === OrderStatus::OUT_FOR_DELIVERY; }
    public function isDelivered(): bool        { return $this->status_enum === OrderStatus::DELIVERED; }
    public function isCompleted(): bool        { return $this->status_enum === OrderStatus::COMPLETED; }
    public function isReturnRequested(): bool  { return $this->status_enum === OrderStatus::RETURN_REQUESTED; }
    public function isReturnApproved(): bool   { return $this->status_enum === OrderStatus::RETURN_APPROVED; }
    public function isReturned(): bool         { return $this->status_enum === OrderStatus::RETURNED; }
    public function isCancelled(): bool        { return $this->status_enum === OrderStatus::CANCELLED; }
    public function isClosed(): bool           { return $this->status_enum === OrderStatus::CLOSED; }

    /**
     * Get the list of available actions for this order (immediate next steps only).
     */
    public function getAvailableActions(): array
    {
        $actions = [];
        $current = $this->status_enum;

        foreach ($current->allowedTransitions() as $next) {
            $actions[] = $this->buildActionItem($next);
        }

        return $actions;
    }

    /**
     * Get ALL reachable downstream statuses (transitive closure).
     * For the dropdown to show full pipeline from current to terminal.
     */
    public function getAllDownstreamActions(): array
    {
        $current = $this->status_enum;
        $visited = [$current->value => true];
        $allActions = [];

        $this->collectDownstream($current, $visited, $allActions);

        return $allActions;
    }

    private function collectDownstream(OrderStatus $from, array &$visited, array &$actions): void
    {
        foreach ($from->allowedTransitions() as $next) {
            if (isset($visited[$next->value])) {
                continue; // avoid cycles
            }
            $visited[$next->value] = true;
            $actions[] = $this->buildActionItem($next);
            $this->collectDownstream($next, $visited, $actions);
        }
    }

    /**
     * Merge both: immediate next actions first, then all remaining downstream.
     */
    public function getPipelineActions(): array
    {
        $immediate = $this->getAvailableActions();
        $allDownstream = $this->getAllDownstreamActions();

        $immediateKeys = array_column($immediate, 'action');
        $pipeline = $immediate;

        foreach ($allDownstream as $item) {
            if (!in_array($item['action'], $immediateKeys, true)) {
                $item['class'] = $item['class'] . ' opacity-50';
                $pipeline[] = $item;
            }
        }

        return $pipeline;
    }

    /**
     * Fast-forward: auto-complete all intermediate steps to reach target.
     * Returns true if all steps succeeded, false otherwise.
     */
    public function fastForwardTo(OrderStatus $target, ?string $note = null, ?int $userId = null): bool
    {
        $current = $this->status_enum;

        // Already at target? Done.
        if ($current === $target) {
            return true;
        }

        // Is it a direct transition?
        if ($current->canTransitionTo($target)) {
            return $this->transitionTo($target, $note, $userId);
        }

        // Find the shortest path using BFS through the transition graph
        $path = $this->findShortestPath($current, $target);
        if (empty($path)) {
            Log::warning('No path found for fast-forward', [
                'order_id'   => $this->id,
                'from'       => $current->value,
                'to'         => $target->value,
            ]);
            return false;
        }

        // Execute each step
        $steps = [$current->value];
        foreach ($path as $step) {
            if (!$this->transitionTo($step, "Auto-step: {$steps[count($steps)-1]} → {$step->value}", $userId)) {
                return false;
            }
            $steps[] = $step->value;
        }

        // Add the final note
        if ($note) {
            $this->addNote($note, 'info', 'system', $userId);
        }

        Log::info('Order fast-forwarded', [
            'order_id'   => $this->id,
            'path'       => implode(' → ', $steps),
            'user_id'    => $userId,
        ]);

        return true;
    }

    /**
     * BFS to find the shortest path from $from to $target.
     * @return OrderStatus[] Array of intermediate steps (excluding $from, including $target)
     */
    private function findShortestPath(OrderStatus $from, OrderStatus $target): array
    {
        if ($from === $target) {
            return [];
        }

        $queue = [[$from]];
        $visited = [$from->value => true];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $last = end($path);

            foreach ($last->allowedTransitions() as $next) {
                if (isset($visited[$next->value])) {
                    continue;
                }
                $visited[$next->value] = true;

                $newPath = array_merge($path, [$next]);

                if ($next === $target) {
                    // Return path excluding the starting node
                    return array_slice($newPath, 1);
                }

                $queue[] = $newPath;
            }
        }

        return [];
    }

    private function buildActionItem(OrderStatus $target): array
    {
        return match ($target) {
            OrderStatus::CONFIRMED => [
                'action'   => 'confirm',
                'label'    => 'Confirm Order',
                'icon'     => 'fa-check-circle',
                'class'    => 'success',
                'next_status' => $target->value,
            ],
            OrderStatus::PICKING => [
                'action'   => 'start_picking',
                'label'    => 'Start Picking',
                'icon'     => 'fa-box-open',
                'class'    => 'primary',
                'next_status' => $target->value,
            ],
            OrderStatus::PACKING => [
                'action'   => 'start_packing',
                'label'    => 'Start Packing',
                'icon'     => 'fa-box',
                'class'    => 'primary',
                'next_status' => $target->value,
            ],
            OrderStatus::PACKED => [
                'action'   => 'mark_packed',
                'label'    => 'Mark as Packed',
                'icon'     => 'fa-boxes',
                'class'    => 'info',
                'next_status' => $target->value,
            ],
            OrderStatus::SHIPPED => [
                'action'   => 'ship',
                'label'    => 'Ship Order',
                'icon'     => 'fa-truck',
                'class'    => 'info',
                'next_status' => $target->value,
            ],
            OrderStatus::OUT_FOR_DELIVERY => [
                'action'   => 'out_for_delivery',
                'label'    => 'Out for Delivery',
                'icon'     => 'fa-shipping-fast',
                'class'    => 'warning',
                'next_status' => $target->value,
            ],
            OrderStatus::DELIVERED => [
                'action'   => 'deliver',
                'label'    => 'Mark as Delivered',
                'icon'     => 'fa-check-double',
                'class'    => 'success',
                'next_status' => $target->value,
            ],
            OrderStatus::COMPLETED => [
                'action'   => 'complete',
                'label'    => 'Complete Order',
                'icon'     => 'fa-flag-checkered',
                'class'    => 'success',
                'next_status' => $target->value,
            ],
            OrderStatus::RETURN_REQUESTED => [
                'action'   => 'request_return',
                'label'    => 'Request Return',
                'icon'     => 'fa-undo',
                'class'    => 'warning',
                'next_status' => $target->value,
            ],
            OrderStatus::RETURN_APPROVED => [
                'action'   => 'approve_return',
                'label'    => 'Approve Return',
                'icon'     => 'fa-check',
                'class'    => 'info',
                'next_status' => $target->value,
            ],
            OrderStatus::RETURNED => [
                'action'   => 'mark_returned',
                'label'    => 'Item Returned',
                'icon'     => 'fa-box-open',
                'class'    => 'secondary',
                'next_status' => $target->value,
            ],
            OrderStatus::CLOSED => [
                'action'   => 'close',
                'label'    => 'Close Order',
                'icon'     => 'fa-lock',
                'class'    => 'dark',
                'next_status' => $target->value,
            ],
            OrderStatus::CANCELLED => [
                'action'   => 'cancel',
                'label'    => 'Cancel Order',
                'icon'     => 'fa-times-circle',
                'class'    => 'danger',
                'next_status' => $target->value,
            ],
            default => [
                'action'   => $target->value,
                'label'    => $target->label(),
                'icon'     => 'fa-arrow-right',
                'class'    => 'secondary',
                'next_status' => $target->value,
            ],
        };
    }

    // ═══════════════════════════════════════════════════════
    // 🏷️  ORDER TYPE
    // ═══════════════════════════════════════════════════════

    public function isPosOrder(): bool
    {
        return $this->order_type === 'pos';
    }

    public function isCodOrder(): bool
    {
        return $this->order_type === 'cod';
    }

    public function isOnlineOrder(): bool
    {
        return $this->order_type === 'online' || $this->order_type === null;
    }

    /**
     * For POS orders — immediately mark as completed (skip fulfillment).
     */
    public function completePosOrder(?string $note = null, ?int $userId = null): bool
    {
        if (!$this->isPosOrder()) {
            return false;
        }
        // POS goes directly to completed
        return $this->transitionTo(OrderStatus::COMPLETED, $note ?? 'POS order completed', $userId);
    }
}
