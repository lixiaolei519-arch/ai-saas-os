<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
