<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expected_value' => 'array',
            'metadata' => 'array',
        ];
    }
}
