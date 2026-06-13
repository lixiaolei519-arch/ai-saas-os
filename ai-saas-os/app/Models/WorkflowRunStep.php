<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRunStep extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
