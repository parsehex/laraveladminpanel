<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected static function booted(): void
    {
        static::creating(function (Permission $permission) {
            if (empty($permission->slug)) {
                $permission->slug = $permission->name;
            }
            if (empty($permission->module_name) && str_contains($permission->name, '.')) {
                $permission->module_name = explode('.', $permission->name)[0];
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
