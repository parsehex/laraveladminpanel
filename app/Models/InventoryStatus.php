<?php

namespace App\Models;

use App\Support\InventoryStatusAppearance;
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
        'color',
        'is_system',
        'sort_order',
        'archived_at',
    ];

    protected $attributes = [
        'color' => '#6b7280',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => InventoryStatusAppearance::flush());
        static::deleted(fn () => InventoryStatusAppearance::flush());
    }

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
     * Active status names for pickers, sorted alphabetically.
     *
     * Manage → Statuses and status breakdown chips still use sort_order for catalog display order.
     *
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return static::query()
            ->active()
            ->orderBy('name')
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
            sort($names, SORT_STRING);
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
