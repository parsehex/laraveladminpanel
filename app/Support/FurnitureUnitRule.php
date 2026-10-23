<?php

namespace App\Support;

use App\Models\TruckAppliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Which furniture units an action may touch, by status (null/empty counts as Triage) and assembly state.
 *
 * The same rule filters queries when picking units and checks individual units or status buckets in PHP.
 */
class FurnitureUnitRule
{
    private const STATUS_SQL = "COALESCE(NULLIF(truck_appliances.status, ''), 'Triage')";

    /**
     * @param  list<string>|null  $onlyStatuses  null allows any status not excluded
     * @param  list<string>  $exceptStatuses
     */
    public function __construct(
        public readonly ?array $onlyStatuses = null,
        public readonly array $exceptStatuses = [],
        public readonly bool $unassembledOnly = false,
    ) {}

    public static function statusName(?string $status): string
    {
        return filled($status) ? $status : 'Triage';
    }

    public function allows(?string $status, bool $assembled): bool
    {
        $status = self::statusName($status);

        if ($this->unassembledOnly && $assembled) {
            return false;
        }

        if ($this->onlyStatuses !== null && ! in_array($status, $this->onlyStatuses, true)) {
            return false;
        }

        return ! in_array($status, $this->exceptStatuses, true);
    }

    public function allowsUnit(TruckAppliance $unit): bool
    {
        return $this->allows($unit->status, (bool) $unit->furnitureDetails?->isAssembled());
    }

    /**
     * @param  Builder<TruckAppliance>  $query
     */
    public function apply(Builder $query): void
    {
        if ($this->onlyStatuses !== null) {
            $query->whereIn(DB::raw(self::STATUS_SQL), $this->onlyStatuses);
        }

        if ($this->exceptStatuses !== []) {
            $query->whereNotIn(DB::raw(self::STATUS_SQL), $this->exceptStatuses);
        }

        if ($this->unassembledOnly) {
            $query->whereDoesntHave('furnitureDetails', fn (Builder $details) => $details->whereNotNull('assembled_at'));
        }
    }

    public function describe(): string
    {
        $parts = [];

        if ($this->onlyStatuses !== null) {
            $parts[] = 'in '.self::listStatuses($this->onlyStatuses, 'or');
        } elseif ($this->exceptStatuses !== []) {
            $parts[] = 'not '.self::listStatuses($this->exceptStatuses, 'or');
        } else {
            $parts[] = 'in any status';
        }

        if ($this->unassembledOnly) {
            $parts[] = 'not yet assembled';
        }

        return implode(', ', $parts);
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function listStatuses(array $statuses, string $conjunction): string
    {
        if (count($statuses) <= 1) {
            return implode('', $statuses);
        }

        return implode(', ', array_slice($statuses, 0, -1)).' '.$conjunction.' '.end($statuses);
    }
}
