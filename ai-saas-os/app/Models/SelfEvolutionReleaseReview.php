<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfEvolutionReleaseReview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'rollback_suggestions' => 'array',
            'deployment_suggestions' => 'array',
            'testing_suggestions' => 'array',
            'security_suggestions' => 'array',
            'business_suggestions' => 'array',
            'requires_approval' => 'boolean',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
