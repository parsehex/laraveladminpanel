<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitAssignment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_BUILT = 'built';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'kit_id',
        'quantity',
        'assigned_to',
        'assigned_by',
        'due_date',
        'notes',
        'platform',
        'status',
        'raw_stock_deducted',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'due_date' => 'date',
            'raw_stock_deducted' => 'boolean',
        ];
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(KitMessage::class, 'assignment_id');
    }
}
