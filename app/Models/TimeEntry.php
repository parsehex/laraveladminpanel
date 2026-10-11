<?php

namespace App\Models;

use App\Enums\TimeActivity;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'activity',
        'user_id',
        'user_name',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'activity' => TimeActivity::class,
            'user_id' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function assembly(Builder $query): void
    {
        $query->where('activity', TimeActivity::Assembly);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at === null || $this->ended_at === null) {
            return null;
        }

        return (int) abs($this->started_at->diffInSeconds($this->ended_at));
    }

    public static function formatSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);

        if ($seconds < 60) {
            return $seconds === 0 ? '0m' : '< 1m';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours === 0) {
            return $minutes.'m';
        }

        return $hours.'h '.$minutes.'m';
    }
}
