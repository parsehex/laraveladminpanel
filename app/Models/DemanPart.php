<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemanPart extends Model
{
    public const CONDITIONS = ['Perfect', 'Good', 'Fair', 'Bad'];

    protected $fillable = [
        'truck_appliance_id',
        'part_number',
        'description',
        'price',
        'condition',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
            'price' => 'decimal:2',
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
