<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'part_number',
        'product_name',
        'model_compatibility',
        'total_stock',
        'retail_price',
        'your_price',
        'cross_reference',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_stock' => 'integer',
            'retail_price' => 'decimal:2',
            'your_price' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
