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
        'unit_label',
        'category_id',
        'subcategory',
        'model_id',
        'serial_number',
        'brand',
        'product_name',
        'quantity',
        'price',
        'msrp',
        'fuel_type',
        'receiving_condition',
        'status',
        'location',
        'total_parts_cost',
        'sold_price',
        'sold_by',
        'sold_at',
        'photos',
        'original_order_number',
        'return_reason',
        'return_problems',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'truck_id' => 'integer',
            'category_id' => 'integer',
            'model_id' => 'integer',
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'msrp' => 'decimal:2',
            'total_parts_cost' => 'decimal:2',
            'sold_price' => 'decimal:2',
            'sold_at' => 'datetime',
            'photos' => 'array',
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusHistories()
    {
        return $this->hasMany(InventoryStatusHistory::class, 'truck_appliance_id');
    }

    public function parts()
    {
        return $this->hasMany(AppliancePart::class, 'truck_appliance_id');
    }

    public function usesNegativePartsCost(): bool
    {
        return in_array($this->status, ['Demanufacture', 'Scrap'], true);
    }

    public function signedPartsCost(): float
    {
        $partsCost = (float) $this->total_parts_cost;

        return $this->usesNegativePartsCost() ? -$partsCost : $partsCost;
    }

    public function totalCostUsing(float $baseCost): float
    {
        return $baseCost + $this->signedPartsCost();
    }
}
