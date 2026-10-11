<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnitureDetail extends Model
{
    protected $fillable = [
        'truck_appliance_id',
        'assembled_at',
        'assembled_by',
        'assembled_by_name',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
            'assembled_at' => 'datetime',
            'assembled_by' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'truck_appliance_id');
    }

    public function assembler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assembled_by');
    }

    public function isAssembled(): bool
    {
        return $this->assembled_at !== null;
    }
}
