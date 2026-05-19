<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sop',
    ];

    public function parts(): HasMany
    {
        return $this->hasMany(KitPart::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(KitInventory::class, 'part_name', 'code');
    }
}
