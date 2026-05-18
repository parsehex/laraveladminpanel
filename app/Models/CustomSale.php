<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomSale extends Model
{
    protected $fillable = [
        'model_number',
        'serial_number',
        'sold_price',
        'estimated_price',
        'sold_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sold_price' => 'decimal:2',
            'estimated_price' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
