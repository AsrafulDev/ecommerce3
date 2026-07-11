<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleAnalyticSetting extends Model
{
    use HasFactory;

    protected $table = 'google_analytics_settings';

    protected $guarded = [];
}
