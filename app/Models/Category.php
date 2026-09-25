<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $attributes = [
        'type' => 'appliance',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => ItemType::class,
        ];
    }

    #[Scope]
    protected function ofType(Builder $query, ItemType $type): void
    {
        $query->where('type', $type);
    }

    public function isFurniture(): bool
    {
        return $this->type === ItemType::Furniture;
    }

    public function models(): HasMany
    {
        return $this->hasMany(\App\Models\Model::class, 'category_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }
}
