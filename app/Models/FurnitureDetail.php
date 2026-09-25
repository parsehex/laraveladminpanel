<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnitureDetail extends Model
{
    protected $fillable = [
        'truck_appliance_id',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'truck_appliance_id');
    }
}
