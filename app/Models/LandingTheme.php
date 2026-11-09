<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A custom colour theme for landing pages: a name and the four page colours.
 */
class LandingTheme extends Model
{
    protected $table = 'landing_themes';

    protected $guarded = [];

    /** @return array{primary: string, accent: string, background: string, text: string} */
    public function colours(): array
    {
        return [
            'primary' => $this->primary,
            'accent' => $this->accent,
            'background' => $this->background,
            'text' => $this->text,
        ];
    }
}
