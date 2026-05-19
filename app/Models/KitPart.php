<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitPart extends Model
{
    protected $fillable = [
        'kit_id',
        'part_name',
        'quantity_per_kit',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_kit' => 'integer',
        ];
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }
}
