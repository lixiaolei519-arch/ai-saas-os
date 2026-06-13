<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_delta' => 'decimal:6',
            'balance_after' => 'decimal:6',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function aiAccount(): BelongsTo
    {
        return $this->belongsTo(AiAccount::class);
    }
}
