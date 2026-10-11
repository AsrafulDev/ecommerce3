<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerAdvance extends Model
{
    protected $fillable = ['customer_id', 'amount', 'payment_method', 'trx_note', 'created_by'];
    protected function casts(): array { return ['amount' => 'decimal:2']; }
    public function customer() { return $this->belongsTo(Customer::class); }
}
