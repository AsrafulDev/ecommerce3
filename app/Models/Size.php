<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function productsizes()
    {
        return $this->hasMany(Productsize::class, 'size_id', 'id');
    }
}
