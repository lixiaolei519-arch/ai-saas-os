<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginDownloadRecord extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'downloaded_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(PluginRelease::class, 'plugin_release_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PluginPackage::class, 'plugin_package_id');
    }
}
