<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckAppliance extends EloquentModel
{
    use SoftDeletes;

    public const APPLIANCE_ONLY_STATUSES = [
        'Testing',
        'Repair',
        'Demanufacture',
        'Holding for parts',
    ];

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

    protected static function booted(): void
    {
        static::saving(function (TruckAppliance $appliance): void {
            if (! $appliance->isDirty('status')) {
                return;
            }

            $location = InventoryStatus::autoLocationFor($appliance->status);

            if ($location !== null) {
                $appliance->location = $location;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'truck_id' => 'integer',
            'category_id' => 'integer',
            'model_id' => 'integer',
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'msrp' => 'decimal:2',
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

    public function furnitureDetails(): HasOne
    {
        return $this->hasOne(FurnitureDetail::class);
    }

    #[Scope]
    protected function ofType(Builder $query, ItemType $type): void
    {
        if ($type === ItemType::Furniture) {
            $query->whereHas('category', fn (Builder $category) => $category->where('type', $type));

            return;
        }

        $query->where(function (Builder $query) use ($type) {
            $query->whereDoesntHave('category')
                ->orWhereHas('category', fn (Builder $category) => $category->where('type', $type));
        });
    }

    #[Scope]
    protected function appliances(Builder $query): void
    {
        $query->ofType(ItemType::Appliance);
    }

    #[Scope]
    protected function furniture(Builder $query): void
    {
        $query->ofType(ItemType::Furniture);
    }

    public function itemType(): ItemType
    {
        return $this->category?->type ?? ItemType::Appliance;
    }

    public function isFurniture(): bool
    {
        return $this->itemType() === ItemType::Furniture;
    }

    public function abortIfFurniture(): void
    {
        abort_if($this->isFurniture(), 404);
    }

    public function syncFurnitureDetails(): void
    {
        if ($this->isFurniture()) {
            $this->furnitureDetails()->firstOrCreate([]);

            return;
        }

        $this->furnitureDetails()->delete();
    }

    public static function idFromScan(?string $payload): ?int
    {
        if ($payload === null) {
            return null;
        }

        $payload = trim($payload);

        if ($payload === '') {
            return null;
        }

        if (ctype_digit($payload)) {
            return (int) $payload;
        }

        if (preg_match('/[?&]id=(\d+)/i', $payload, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('#/(?:admin/)?inventory/(\d+)#i', $payload, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function findForSale(string $input): ?self
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        $id = self::idFromScan($input);

        if ($id !== null) {
            $match = self::query()->find($id);

            if ($match) {
                return $match;
            }
        }

        $serial = strtoupper((string) preg_replace('/[^A-Z0-9-]/', '', $input));

        if ($serial === '') {
            return null;
        }

        return self::query()->where('serial_number', $serial)->first();
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(Model::class);
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

    public function demanParts()
    {
        return $this->hasMany(DemanPart::class, 'truck_appliance_id');
    }

    public function testingResults()
    {
        return $this->hasMany(TestingResult::class, 'truck_appliance_id');
    }

    public function repairResults()
    {
        return $this->hasMany(RepairResult::class, 'truck_appliance_id');
    }

    public function repairDiagnoses()
    {
        return $this->hasMany(RepairDiagnosis::class, 'truck_appliance_id');
    }

    public function partsCost(): float
    {
        if (array_key_exists('parts_sum_cost', $this->attributes)) {
            return (float) ($this->attributes['parts_sum_cost'] ?? 0);
        }

        if ($this->relationLoaded('parts')) {
            return (float) $this->parts->sum('cost');
        }

        return (float) $this->parts()->sum('cost');
    }

    public function totalCost(): float
    {
        return (float) $this->price + $this->partsCost();
    }

    public function salesCost(): float
    {
        return (float) $this->price;
    }

    public static function partsCostSql(string $applianceTable = 'truck_appliances'): string
    {
        return "COALESCE((SELECT SUM(cost) FROM appliance_parts WHERE appliance_parts.truck_appliance_id = {$applianceTable}.id), 0)";
    }

    public static function totalCostSql(string $applianceTable = 'truck_appliances'): string
    {
        return '(COALESCE('.$applianceTable.'.price, 0) + '.self::partsCostSql($applianceTable).')';
    }
}
