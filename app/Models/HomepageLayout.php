<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageLayout extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'is_default', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(HomepageLayoutSection::class, 'layout_id')->orderBy('sort_order');
    }

    public static function getActive()
    {
        return static::with(['sections' => function ($q) {
            $q->where('is_visible', true)->orderBy('sort_order');
        }, 'sections.section'])->where('is_active', true)->first();
    }
}
