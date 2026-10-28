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
        'warranty_enabled' => 'boolean',
        'header_components' => 'array',
        'footer_components' => 'array',
    ];

    public function activeTheme()
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function activeLayout()
    {
        return $this->belongsTo(HomepageLayout::class, 'active_layout_id');
    }

    /**
     * The copyright line every footer shows. The admin types it in General Settings;
     * {year} becomes the current year and {name} the site name. A blank field uses the default line.
     */
    public function getCopyrightTextAttribute(): string
    {
        $text = trim((string) $this->copyright) ?: '© {year} {name}. All rights reserved.';

        return strtr($text, [
            '{year}' => date('Y'),
            '{name}' => (string) $this->name,
        ]);
    }
}
