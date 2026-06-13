<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginInstallation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'installed_at' => 'datetime',
        ];
    }
}
