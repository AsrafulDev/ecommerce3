<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageLayoutSection extends Model
{
    protected $fillable = [
        'layout_id', 'section_id', 'sort_order',
        'is_visible', 'columns_config', 'extra_settings', 'breakpoints',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'extra_settings' => 'json',
        'breakpoints' => 'json',
    ];

    public function layout()
    {
        return $this->belongsTo(HomepageLayout::class, 'layout_id');
    }

    public function section()
    {
        return $this->belongsTo(HomepageSection::class, 'section_id');
    }
}
