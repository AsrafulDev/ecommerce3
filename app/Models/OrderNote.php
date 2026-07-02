<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ═══════════════════════════════════════════════════════
    // 🔗  Relationships
    // ═══════════════════════════════════════════════════════

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ═══════════════════════════════════════════════════════
    // 📦  Scopes
    // ═══════════════════════════════════════════════════════

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAdminNotes($query)
    {
        return $query->where('source', 'admin');
    }

    public function scopeSystemNotes($query)
    {
        return $query->where('source', 'system');
    }

    // ═══════════════════════════════════════════════════════
    // 🏭  Factory Helper
    // ═══════════════════════════════════════════════════════

    /**
     * Create a new note on an order.
     */
    public static function addTo(
        Order $order,
        string $content,
        string $type = 'info',
        string $source = 'admin',
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'order_id'  => $order->id,
            'user_id'   => $userId ?? (auth()->check() ? auth()->id() : null),
            'content'   => $content,
            'type'      => $type,
            'source'    => $source,
            'metadata'  => $metadata,
        ]);
    }
}
