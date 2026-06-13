<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiCompanyReleasePlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'quality_gate' => 'array',
            'requires_approval' => 'boolean',
            'simulation_mode' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
