<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestingFlowVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'testing_flow_id',
        'version',
        'name',
        'start',
        'steps',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'steps' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(TestingFlow::class, 'testing_flow_id');
    }
}
