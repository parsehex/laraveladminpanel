<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCheckIn extends Model
{
    protected $fillable = [
        'user_id',
        'photos',
        'extraction',
        'truck_appliance_id',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'photos' => 'array',
            'extraction' => 'array',
            'truck_appliance_id' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'truck_appliance_id');
    }
}
