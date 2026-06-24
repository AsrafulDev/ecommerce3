<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
        'show_all_products' => 'boolean',
        'show_category_wise_products' => 'boolean',
    ];

    public function activeTheme()
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function activeLayout()
    {
        return $this->belongsTo(HomepageLayout::class, 'active_layout_id');
    }
}
