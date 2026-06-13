<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCompanyQualityReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'checks' => 'array',
            'gaps' => 'array',
            'recommendations' => 'array',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
