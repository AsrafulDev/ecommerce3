<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of the Mushak 6.3 audit trail. Insert-only: rows are written and never changed or deleted.
 */
class VatChallanEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function record(string $event, ?int $challanId, ?int $orderId, ?int $userId, ?string $ip, array $details = []): self
    {
        return self::create([
            'vat_challan_id' => $challanId,
            'order_id' => $orderId,
            'event' => $event,
            'user_id' => $userId,
            'ip' => $ip,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    /** Insert-only: an update would rewrite history, so it is refused. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('VAT challan audit events are insert-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('VAT challan audit events are insert-only.');
    }
}
