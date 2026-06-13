<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionLink extends Model
{
    protected $guarded = [];

    protected $appends = [
        'tracking_url',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function getTrackingUrlAttribute(): string
    {
        $separator = str_contains($this->destination_url, '?') ? '&' : '?';

        return $this->destination_url.$separator.'ref='.$this->code;
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'marketing_channel_id');
    }
}
