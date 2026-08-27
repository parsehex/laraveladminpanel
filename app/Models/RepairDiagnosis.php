<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairDiagnosis extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'truck_appliance_id',
        'diagnosis',
        'user_id',
        'user_name',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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

    /**
     * @return array{id: string, diagnosis: string, user_id: int, user_name: string, created_at: string}
     */
    public function toPayload(): array
    {
        return [
            'id' => (string) $this->uuid,
            'diagnosis' => (string) $this->diagnosis,
            'user_id' => (int) ($this->user_id ?? 0),
            'user_name' => (string) ($this->user_name ?? ''),
            'created_at' => optional($this->created_at)?->utc()->toIso8601String() ?? '',
        ];
    }
}
