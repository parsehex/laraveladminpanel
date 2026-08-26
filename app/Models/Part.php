<?php

namespace App\Models;

use App\Models\Model as ApplianceModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Part extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'part_number',
        'product_name',
        // DEPRECATED / not source of truth: do not query or write this for model↔part links.
        // Use the model_parts table (Part::models() / Model::parts()). Kept only as a legacy denormalized cache.
        'model_compatibility',
        'total_stock',
        'retail_price',
        'your_price',
        'cross_reference',
        'diagram_name',
        'image_url',
        'make',
        'item',
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

    public function models(): BelongsToMany
    {
        return $this->belongsToMany(ApplianceModel::class, 'model_parts')
            ->withPivot('variation')
            ->withTimestamps();
    }

    /**
     * Distinct model numbers from model_parts, falling back to legacy model_compatibility.
     */
    protected function compatibleModelNumbers(): Attribute
    {
        return Attribute::get(function () {
            $fromPivot = $this->relationLoaded('models')
                ? $this->models->pluck('model_number')
                : $this->models()->pluck('models.model_number');

            $numbers = $fromPivot->filter()->unique()->sort()->values();

            if ($numbers->isNotEmpty()) {
                return $numbers;
            }

            $legacy = trim((string) $this->attributes['model_compatibility'] ?? '');

            return $legacy !== '' ? collect([$legacy]) : collect();
        });
    }

    protected function compatibleModelsLabel(): Attribute
    {
        return Attribute::get(function () {
            $label = $this->compatible_model_numbers->implode(', ');

            return $label !== '' ? $label : null;
        });
    }

    /**
     * Sync model_parts links for this part. Preserves existing variation rows for kept models;
     * new links use variation "default". Also refreshes denormalized model_compatibility.
     *
     * @param  array<int|string>  $modelIds
     */
    public function syncCompatibleModels(array $modelIds): void
    {
        $modelIds = collect($modelIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $currentIds = DB::table('model_parts')
            ->where('part_id', $this->id)
            ->distinct()
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id);

        $toRemove = $currentIds->diff($modelIds);
        if ($toRemove->isNotEmpty()) {
            DB::table('model_parts')
                ->where('part_id', $this->id)
                ->whereIn('model_id', $toRemove)
                ->delete();
        }

        $toAdd = $modelIds->diff($currentIds);
        $now = now();

        foreach ($toAdd as $modelId) {
            DB::table('model_parts')->insert([
                'model_id' => $modelId,
                'part_id' => $this->id,
                'variation' => 'default',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $label = ApplianceModel::query()
            ->whereIn('id', $modelIds)
            ->orderBy('model_number')
            ->pluck('model_number')
            ->implode(', ');

        $this->forceFill([
            'model_compatibility' => $label !== '' ? $label : null,
        ])->saveQuietly();
    }
}
