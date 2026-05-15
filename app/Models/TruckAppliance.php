<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckAppliance extends EloquentModel
{
    use SoftDeletes;

    public const RECEIVING_CONDITIONS = [
        'A-Grade',
        'B-Grade',
        'C-Grade',
        'D-Grade',
    ];

    protected $fillable = [
        'truck_id',
        'category_id',
        'model_id',
        'serial_number',
        'brand',
        'product_name',
        'msrp',
        'receiving_condition',
        'total_parts_cost',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'truck_id' => 'integer',
            'category_id' => 'integer',
            'model_id' => 'integer',
            'msrp' => 'decimal:2',
            'total_parts_cost' => 'decimal:2',
        ];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Model::class);
    }
}
