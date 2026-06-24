<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'preview_image',
        'is_system', 'is_active', 'settings_schema',
        'default_columns', 'default_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'settings_schema' => 'json',
    ];

    public function layoutSections()
    {
        return $this->hasMany(HomepageLayoutSection::class, 'section_id');
    }
}
