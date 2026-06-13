<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRecord extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'calculated_at' => 'datetime',
        ];
    }
}
