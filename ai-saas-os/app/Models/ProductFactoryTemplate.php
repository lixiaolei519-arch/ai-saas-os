<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFactoryTemplate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'requires_approval' => 'boolean',
            'simulation_mode' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(ProductFactoryDraft::class);
    }
}
