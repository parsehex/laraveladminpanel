<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'username',
        'user_id',
        'action_type',
        'item_id',
        'extra',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'created_at' => 'datetime',
            'user_id' => 'integer',
            'item_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TruckAppliance::class, 'item_id');
    }

    /**
     * Record a staff action. Failures are swallowed so logging never breaks the main flow.
     *
     * @param  array<string, mixed>|null  $extra
     */
    public static function log(string $actionType, ?int $itemId = null, ?array $extra = null, ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return;
        }

        try {
            static::query()->create([
                'username' => $user->name,
                'user_id' => $user->id,
                'action_type' => $actionType,
                'item_id' => $itemId,
                'extra' => $extra,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to log user action', [
                'action_type' => $actionType,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function actionTypeForStatus(string $status): ?string
    {
        // Matches the old appliance.php status→action switch (only these were logged).
        return match ($status) {
            'Testing' => 'test_unit',
            'Cleaning' => 'clean_unit',
            'Repair' => 'repair_unit',
            'Ready' => 'ready_unit',
            'Demanufacture' => 'deman_unit',
            'Show Room' => 'showroom_sent',
            'Sold' => 'showroom_sold',
            default => null,
        };
    }
}
