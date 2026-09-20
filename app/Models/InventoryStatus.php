<?php

namespace App\Models;

use Database\Factories\InventoryStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStatus extends Model
{
    /** @use HasFactory<InventoryStatusFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'auto_location',
        'is_system',
        'sort_order',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return static::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    /**
     * Active catalog names, plus a current value so archived statuses remain selectable on items that still have them.
     *
     * @return list<string>
     */
    public static function assignableNames(?string $current = null): array
    {
        $names = static::activeNames();

        if ($current !== null && $current !== '' && ! in_array($current, $names, true)) {
            $names[] = $current;
        }

        return $names;
    }

    public static function autoLocationFor(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        $location = static::query()
            ->where('name', $status)
            ->value('auto_location');

        if (! is_string($location) || $location === '') {
            return null;
        }

        return $location;
    }

    public static function nextSortOrder(): int
    {
        return (int) static::query()->max('sort_order') + 1;
    }

    public function isInUse(): bool
    {
        return TruckAppliance::query()->where('status', $this->name)->exists();
    }

    public function canBeArchived(): bool
    {
        return ! $this->is_system && $this->archived_at === null && ! $this->isInUse();
    }
}
