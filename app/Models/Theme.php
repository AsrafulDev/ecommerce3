<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'is_default', 'is_active', 'preview_image',
        'primary_color', 'secondary_color', 'accent_color',
        'text_color', 'heading_color', 'body_bg_color',
        'header_bg_color', 'header_text_color',
        'footer_bg_color', 'footer_text_color',
        'copyright_bg_color', 'copyright_text_color',
        'button_bg_color', 'button_text_color', 'button_hover_bg_color',
        'border_color', 'sale_badge_bg', 'sale_badge_text',
        'font_family', 'heading_font', 'body_font_size', 'heading_font_weight',
        'layout_style', 'border_radius', 'card_shadow',
        'custom_css', 'page_custom_css',
        // Admin panel colors
        'sidebar_bg_color', 'sidebar_text_color', 'topbar_bg_color', 'admin_card_bg',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
