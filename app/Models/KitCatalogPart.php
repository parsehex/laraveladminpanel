<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitCatalogPart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'part_number',
        'product_name',
        // Kit parts still store compatibility as a comma-separated string on this table.
        // Regular appliance parts use the model_parts lookup table instead — do not copy that pattern here without a kit-specific pivot.
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

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function compatibilityModelNumbers()
    {
        return collect(preg_split('/\s*[,;|]\s*/', (string) ($this->model_compatibility ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values();
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
