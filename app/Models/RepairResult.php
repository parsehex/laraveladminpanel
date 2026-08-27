<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairResult extends Model
{
    protected $fillable = [
        'result_id',
        'truck_appliance_id',
        'type',
        'source_testing_result_id',
        'source_flow_slug',
        'source_flow_version',
        'resulting_status',
        'answers',
        'failed_steps_snapshot',
        'user_id',
        'user_name',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_flow_version' => 'integer',
            'answers' => 'array',
            'failed_steps_snapshot' => 'array',
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
            'type' => (string) ($this->type ?: 'reevaluation'),
            'source_testing_result_id' => $this->source_testing_result_id,
            'source_flow_slug' => $this->source_flow_slug,
            'source_flow_version' => $this->source_flow_version,
            'resulting_status' => (string) $this->resulting_status,
            'answers' => $this->answers ?? [],
            'failed_steps_snapshot' => $this->failed_steps_snapshot ?? [],
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'completed_at' => optional($this->completed_at)?->utc()->toIso8601String(),
        ];
    }
}
