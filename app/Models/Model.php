<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends EloquentModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'model_number',
        'product_name',
        'category_id',
        'brand',
        'msrp',
        'variations',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'msrp' => 'decimal:2',
            'variations' => 'array',
            'status' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'model_parts')
            ->withPivot('variation')
            ->withTimestamps();
    }

    /**
     * Distinct parts linked via model_parts (source of truth for model↔part).
     */
    public function relatedParts(): BelongsToMany
    {
        return $this->parts()->distinct();
    }
}
