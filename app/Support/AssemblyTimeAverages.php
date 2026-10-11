<?php

namespace App\Support;

use App\Enums\ItemType;
use App\Enums\TimeActivity;
use App\Models\TruckAppliance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssemblyTimeAverages
{
    /**
     * Average completed assembly person-time per furniture item, grouped by category name.
     *
     * @return Collection<int, array{category: string, item_count: int, average_seconds: int}>
     */
    public function byCategory(): Collection
    {
        $itemTotals = DB::table('time_entries')
            ->select('trackable_id')
            ->selectRaw('SUM(EXTRACT(EPOCH FROM (ended_at - started_at))) as person_seconds')
            ->where('activity', TimeActivity::Assembly->value)
            ->where('trackable_type', (new TruckAppliance)->getMorphClass())
            ->whereNotNull('ended_at')
            ->whereNotNull('started_at')
            ->groupBy('trackable_id');

        return DB::query()
            ->fromSub($itemTotals, 'item_totals')
            ->join('truck_appliances', 'truck_appliances.id', '=', 'item_totals.trackable_id')
            ->join('categories', 'categories.id', '=', 'truck_appliances.category_id')
            ->where('categories.type', ItemType::Furniture->value)
            ->whereNull('truck_appliances.deleted_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('trucks')
                    ->whereColumn('trucks.id', 'truck_appliances.truck_id')
                    ->whereNull('trucks.deleted_at');
            })
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->select('categories.name as category')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('AVG(item_totals.person_seconds) as average_seconds')
            ->get()
            ->map(fn (object $row): array => [
                'category' => (string) $row->category,
                'item_count' => (int) $row->item_count,
                'average_seconds' => (int) round((float) $row->average_seconds),
            ])
            ->values();
    }
}
