<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
