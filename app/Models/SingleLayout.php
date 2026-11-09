<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A saved product detail page design. Its settings are read through App\Services\SingleLayout\SingleLayout.
 */
class SingleLayout extends Model
{
    protected $table = 'single_layouts';

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}
