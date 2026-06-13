<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCompanyDailyReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'product' => 'array',
            'technology' => 'array',
            'sales' => 'array',
            'risks' => 'array',
            'next_steps' => 'array',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
