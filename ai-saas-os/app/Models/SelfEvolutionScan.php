<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfEvolutionScan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'metrics' => 'array',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
