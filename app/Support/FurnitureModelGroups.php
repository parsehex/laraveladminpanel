<?php

namespace App\Support;

use App\Models\InventoryStatus;
use App\Models\TruckAppliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rolls live furniture units up into one row per catalog model.
 *
 * A unit without a model can't be grouped, so it gets a row of its own (keyed by unit id).
 * List filters decide which rows are included; counts always cover every live unit of a model,
 * matching how the trucks list filters by item status.
 */
class FurnitureModelGroups
{
    /** Groups model-less units one per row; null for units that have a model. */
    private const UNIT_KEY_SQL = 'CASE WHEN truck_appliances.model_id IS NULL THEN truck_appliances.id END';

    private const STATUS_SQL = "COALESCE(NULLIF(truck_appliances.status, ''), 'Triage')";

    /**
     * @return Builder<TruckAppliance>
     */
    public static function liveUnits(): Builder
    {
        return TruckAppliance::query()->furniture()->onLiveTruck();
    }

    /**
     * Collection key for a row: the model id, or "unit:{id}" for a unit without a model.
     */
    public static function key(?int $modelId, ?int $unitId = null): int|string
    {
        return $modelId ?? 'unit:'.$unitId;
    }

    /**
     * One row per model that has at least one unit in $matchingUnits, plus one row per matching unit
     * that has no model.
     *
     * Rows expose model_id, unit_id (model-less rows only), unit_label (model-less rows only),
     * group_product_name, group_model_number, group_brand, group_category, unit_count and last_received_at.
     *
     * @param  Builder<TruckAppliance>  $matchingUnits  live furniture units with list filters applied
     * @return Builder<TruckAppliance>
     */
    public function query(Builder $matchingUnits): Builder
    {
        return self::liveUnits()
            ->leftJoin('models', 'models.id', '=', 'truck_appliances.model_id')
            ->leftJoin('categories as model_categories', 'model_categories.id', '=', 'models.category_id')
            ->leftJoin('categories as unit_categories', 'unit_categories.id', '=', 'truck_appliances.category_id')
            ->where(function (Builder $query) use ($matchingUnits) {
                $query->whereIn('truck_appliances.model_id', (clone $matchingUnits)->select('truck_appliances.model_id'))
                    ->orWhereIn('truck_appliances.id', (clone $matchingUnits)->whereNull('truck_appliances.model_id')->select('truck_appliances.id'));
            })
            ->groupBy('truck_appliances.model_id', 'models.id', 'model_categories.id')
            ->groupByRaw(self::UNIT_KEY_SQL)
            ->select('truck_appliances.model_id')
            ->selectRaw(self::UNIT_KEY_SQL.' as unit_id')
            ->selectRaw('CASE WHEN truck_appliances.model_id IS NULL THEN MIN(truck_appliances.unit_label) END as unit_label')
            ->selectRaw("COALESCE(NULLIF(models.product_name, ''), MIN(truck_appliances.product_name)) as group_product_name")
            ->selectRaw('models.model_number as group_model_number')
            ->selectRaw("COALESCE(NULLIF(models.brand, ''), MIN(NULLIF(truck_appliances.brand, ''))) as group_brand")
            ->selectRaw('COALESCE(model_categories.name, MIN(unit_categories.name)) as group_category')
            ->selectRaw('COUNT(*) as unit_count')
            ->selectRaw('MAX(truck_appliances.created_at) as last_received_at')
            ->withCasts([
                'unit_id' => 'integer',
                'unit_count' => 'integer',
                'last_received_at' => 'datetime',
            ]);
    }

    /**
     * Unit counts per status for each row, in status catalog order, keyed by key().
     *
     * @param  list<int>  $modelIds
     * @param  list<int>  $unitIds  model-less units shown as their own rows
     * @return Collection<int|string, list<array{status: string, count: int}>>
     */
    public function statusCounts(array $modelIds, array $unitIds = []): Collection
    {
        $rows = $this->unitsForGroups($modelIds, $unitIds)
            ->select('truck_appliances.model_id')
            ->selectRaw(self::UNIT_KEY_SQL.' as unit_id')
            ->selectRaw(self::STATUS_SQL.' as status_name')
            ->selectRaw('COUNT(*) as unit_count')
            ->groupBy('truck_appliances.model_id')
            ->groupByRaw(self::UNIT_KEY_SQL)
            ->groupByRaw(self::STATUS_SQL)
            ->toBase()
            ->get();

        return $rows
            ->groupBy(fn (object $row) => self::key($row->model_id, $row->unit_id))
            ->map(fn (Collection $groupRows) => $this->sortedStatuses($groupRows));
    }

    /**
     * Unit counts per truck for each row, ordered by truck name, keyed by key().
     *
     * @param  list<int>  $modelIds
     * @param  list<int>  $unitIds  model-less units shown as their own rows
     * @return Collection<int|string, list<array{truck_id: int, truck_name: string, count: int}>>
     */
    public function truckCounts(array $modelIds, array $unitIds = []): Collection
    {
        $rows = $this->unitsForGroups($modelIds, $unitIds)
            ->join('trucks', 'trucks.id', '=', 'truck_appliances.truck_id')
            ->select('truck_appliances.model_id', 'trucks.id as truck_id', 'trucks.name as truck_name')
            ->selectRaw(self::UNIT_KEY_SQL.' as unit_id')
            ->selectRaw('COUNT(*) as unit_count')
            ->groupBy('truck_appliances.model_id', 'trucks.id', 'trucks.name')
            ->groupByRaw(self::UNIT_KEY_SQL)
            ->orderBy('trucks.name')
            ->toBase()
            ->get();

        return $rows
            ->groupBy(fn (object $row) => self::key($row->model_id, $row->unit_id))
            ->map(fn (Collection $groupRows) => $groupRows
                ->map(fn (object $row) => [
                    'truck_id' => (int) $row->truck_id,
                    'truck_name' => (string) $row->truck_name,
                    'count' => (int) $row->unit_count,
                ])
                ->values()
                ->all());
    }

    /**
     * Per-truck unit totals and status counts for one model, ordered by truck name.
     *
     * @return list<array{truck_id: int, truck_name: string, count: int, statuses: list<array{status: string, count: int}>}>
     */
    public function truckStatusCounts(int $modelId): array
    {
        $rows = self::liveUnits()
            ->where('truck_appliances.model_id', $modelId)
            ->join('trucks', 'trucks.id', '=', 'truck_appliances.truck_id')
            ->select('trucks.id as truck_id', 'trucks.name as truck_name')
            ->selectRaw(self::STATUS_SQL.' as status_name')
            ->selectRaw('COUNT(*) as unit_count')
            ->groupBy('trucks.id', 'trucks.name')
            ->groupByRaw(self::STATUS_SQL)
            ->orderBy('trucks.name')
            ->toBase()
            ->get();

        return $rows
            ->groupBy('truck_id')
            ->map(fn (Collection $truckRows) => [
                'truck_id' => (int) $truckRows->first()->truck_id,
                'truck_name' => (string) $truckRows->first()->truck_name,
                'count' => (int) $truckRows->sum('unit_count'),
                'statuses' => $this->sortedStatuses($truckRows),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $modelIds
     * @param  list<int>  $unitIds
     * @return Builder<TruckAppliance>
     */
    private function unitsForGroups(array $modelIds, array $unitIds): Builder
    {
        return self::liveUnits()->where(function (Builder $query) use ($modelIds, $unitIds) {
            $query->whereIn('truck_appliances.model_id', $modelIds)
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('truck_appliances.model_id')
                    ->whereIn('truck_appliances.id', $unitIds));
        });
    }

    /**
     * @param  Collection<int, object>  $rows  rows with status_name and unit_count
     * @return list<array{status: string, count: int}>
     */
    private function sortedStatuses(Collection $rows): array
    {
        $order = $this->statusOrder();

        return $rows
            ->sortBy([
                fn (object $a, object $b) => ($order[$a->status_name] ?? PHP_INT_MAX) <=> ($order[$b->status_name] ?? PHP_INT_MAX),
                fn (object $a, object $b) => strcmp($a->status_name, $b->status_name),
            ])
            ->map(fn (object $row) => [
                'status' => (string) $row->status_name,
                'count' => (int) $row->unit_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function statusOrder(): array
    {
        return once(fn () => InventoryStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->flip()
            ->all());
    }
}
