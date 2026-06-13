<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfEvolutionScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'dimensions' => 'array',
            'recommendations' => 'array',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
