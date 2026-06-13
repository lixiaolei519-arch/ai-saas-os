<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiCompanyRoadmap extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'simulation_mode' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
