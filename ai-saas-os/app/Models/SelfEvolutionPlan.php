<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfEvolutionPlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tasks' => 'array',
            'version_plan' => 'array',
            'requires_approval' => 'boolean',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
