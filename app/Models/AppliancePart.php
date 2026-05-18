<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppliancePart extends Model
{
    protected $fillable = [
        'truck_appliance_id',
        'part_id',
        'description',
        'part_number',
        'cost',
        'source',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
            'part_id' => 'integer',
            'cost' => 'decimal:2',
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

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
