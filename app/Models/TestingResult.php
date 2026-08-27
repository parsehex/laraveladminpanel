<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestingResult extends Model
{
    protected $fillable = [
        'result_id',
        'truck_appliance_id',
        'flow_slug',
        'flow_version',
        'resulting_status',
        'answers',
        'notes',
        'flow_snapshot',
        'user_id',
        'user_name',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'flow_version' => 'integer',
            'answers' => 'array',
            'flow_snapshot' => 'array',
            'completed_at' => 'datetime',
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
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'result_id' => $this->result_id,
            'appliance_id' => (int) $this->truck_appliance_id,
            'flow_slug' => (string) $this->flow_slug,
            'flow_version' => (int) $this->flow_version,
            'resulting_status' => (string) $this->resulting_status,
            'answers' => $this->answers ?? [],
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'completed_at' => optional($this->completed_at)?->utc()->toIso8601String(),
            'flow_snapshot' => $this->flow_snapshot ?? [],
        ];
    }
}
