<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStatusHistory extends Model
{
    protected $fillable = [
        'truck_appliance_id',
        'status',
        'notes',
        'parts_ordered',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
            'parts_ordered' => 'boolean',
        ];
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'truck_appliance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
