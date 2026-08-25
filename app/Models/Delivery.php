<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Delivery extends Model
{
    public const PICKER_STATUSES = [
        'Ready',
        'Show Room',
        'Sold',
    ];

    protected $fillable = [
        'customer_name',
        'customer_number',
        'customer_address',
        'order_appliances',
        'notes',
        'delivery_fee',
        'delivery_timeframe',
        'delivery_type',
        'haul_away',
        'collect_payment',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'haul_away' => 'boolean',
            'collect_payment' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appliances(): BelongsToMany
    {
        return $this->belongsToMany(
            TruckAppliance::class,
            'delivery_truck_appliance',
            'delivery_id',
            'truck_appliance_id'
        )->withTimestamps();
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public static function applianceLabel(TruckAppliance $appliance): string
    {
        $parts = array_filter([
            trim((string) ($appliance->brand ?: '')),
            trim((string) ($appliance->product_name ?: '')),
            $appliance->model?->model_number
                ? 'Model '.$appliance->model->model_number
                : null,
            $appliance->serial_number
                ? 'SN '.$appliance->serial_number
                : null,
            $appliance->status ?: null,
        ]);

        return implode(' · ', $parts) ?: ('Unit #'.$appliance->id);
    }

    /**
     * @param  Collection<int, TruckAppliance>|iterable<TruckAppliance>  $appliances
     */
    public static function snapshotFromAppliances(iterable $appliances): string
    {
        return collect($appliances)
            ->map(fn (TruckAppliance $appliance) => self::applianceLabel($appliance))
            ->filter()
            ->values()
            ->implode("\n");
    }
}
