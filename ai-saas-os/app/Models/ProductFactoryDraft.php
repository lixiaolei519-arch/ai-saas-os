<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFactoryDraft extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'simulation_mode' => 'boolean',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProductFactoryTemplate::class, 'product_factory_template_id');
    }
}
