<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestingFlow extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'start',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'steps' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TestingFlowVersion::class);
    }
}
