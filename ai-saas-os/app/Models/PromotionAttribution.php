<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionAttribution extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'attributed_at' => 'datetime',
        ];
    }
}
