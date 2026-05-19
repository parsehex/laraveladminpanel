<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_number',
        'customer_address',
        'order_appliances',
        'delivery_fee',
        'delivery_timeframe',
        'delivery_type',
        'haul_away',
        'collect_payment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'haul_away' => 'boolean',
            'collect_payment' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
