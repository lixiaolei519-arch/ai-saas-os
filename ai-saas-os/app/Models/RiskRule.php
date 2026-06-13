<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'metadata' => 'array',
        ];
    }
}
