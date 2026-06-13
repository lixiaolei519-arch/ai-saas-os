<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiCompanyIdea extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'simulation_mode' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
