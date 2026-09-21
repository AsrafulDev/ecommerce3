<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'phone_normalized',
        'reason',
    ];

    /**
     * Keep `phone_normalized` in sync with `phone` on every write so the
     * canonical form can never drift from what the admin typed.
     */
    protected static function booted(): void
    {
        static::saving(function (self $block) {
            $block->phone_normalized = normalize_phone($block->phone);
        });
    }

    public function scopeForPhone($query, ?string $phone)
    {
        return $query->where('phone_normalized', normalize_phone($phone));
    }
}
