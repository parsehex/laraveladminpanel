<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemanFlowPrompt extends Model
{
    protected $fillable = [
        'deman_flow_id',
        'key',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(DemanFlow::class, 'deman_flow_id');
    }
}
