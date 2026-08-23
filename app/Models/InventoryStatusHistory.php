<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStatusHistory extends Model
{
    protected $fillable = [
        'truck_appliance_id',
        'status',
        'notes',
        'parts_ordered',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'truck_appliance_id' => 'integer',
            'parts_ordered' => 'boolean',
        ];
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'truck_appliance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function testingResultId(): ?string
    {
        if (! preg_match('/\[testing-result:([^\]]+)\]/', (string) ($this->notes ?? ''), $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function displayNotes(): string
    {
        $notes = trim(preg_replace('/\s*\[testing-result:[^\]]+\]/', '', (string) ($this->notes ?? '')) ?? '');

        return $notes !== '' ? $notes : 'N/A';
    }
}
