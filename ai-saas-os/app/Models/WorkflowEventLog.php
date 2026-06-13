<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEventLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
