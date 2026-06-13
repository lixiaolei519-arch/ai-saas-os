<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCompanyRiskReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'risks' => 'array',
            'mitigations' => 'array',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
