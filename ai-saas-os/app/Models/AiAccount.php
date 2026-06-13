<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance_amount' => 'decimal:6',
            'frozen_amount' => 'decimal:6',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
