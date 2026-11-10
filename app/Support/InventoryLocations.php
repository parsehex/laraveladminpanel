<?php

namespace App\Support;

use App\Models\InventoryLocation;
use App\Models\TruckAppliance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Managed floor locations plus labels currently typed on items.
 *
 * Status auto-locations (SOLD, SHOWROOM, Scrap, …) stay free-text on items and are not
 * catalogued; they still appear in suggestions whenever they are in use.
 */
class InventoryLocations
{
    /**
     * Exact location labels on live-truck items (case variants stay separate).
     *
     * @return Collection<int, array{name: string, item_count: int}>
     */
    public function inUseExact(): Collection
    {
        return TruckAppliance::query()
            ->onLiveTruck()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->select('location')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('location')
            ->orderBy('location')
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'name' => (string) $row->location,
                'item_count' => (int) $row->usage_count,
            ]);
    }

    /**
     * Distinct location labels collapsed by lowercased trim (for pickers).
     *
     * @return Collection<string, array{label: string, count: int}>
     */
    public function inUseCollapsed(): Collection
    {
        return TruckAppliance::query()
            ->onLiveTruck()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->selectRaw('MIN(location) as location')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy(DB::raw('LOWER(TRIM(location))'))
            ->orderBy('location')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row) => [
                strtolower(trim((string) $row->location)) => [
                    'label' => (string) $row->location,
                    'count' => (int) $row->usage_count,
                ],
            ]);
    }

    /**
     * Catalog names for Manage → Locations, with live item counts (0 is fine).
     *
     * @return list<array{name: string, item_count: int}>
     */
    public function catalogWithCounts(): array
    {
        $inUse = $this->inUseCollapsed();

        return InventoryLocation::query()
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryLocation $location) => [
                'name' => $location->name,
                'item_count' => $inUse->get(strtolower(trim($location->name)), ['count' => 0])['count'],
            ])
            ->all();
    }

    /**
     * Exact labels on items that are not in the catalog (status auto-locations, typos, one-offs).
     *
     * @return list<array{name: string, item_count: int}>
     */
    public function uncataloguedInUse(): array
    {
        $catalogKeys = InventoryLocation::query()
            ->pluck('name')
            ->map(fn (string $name) => strtolower(trim($name)))
            ->all();

        return $this->inUseExact()
            ->reject(fn (array $row) => in_array(strtolower(trim($row['name'])), $catalogKeys, true))
            ->values()
            ->all();
    }

    /**
     * Suggestions for location pickers: catalog ∪ currently used.
     *
     * @return list<array{label: string, count: int}>
     */
    public function suggestions(): array
    {
        $inUse = $this->inUseCollapsed();
        $labels = [];

        foreach (InventoryLocation::query()->orderBy('name')->pluck('name') as $name) {
            $key = strtolower(trim($name));
            $labels[$key] = [
                'label' => $name,
                'count' => $inUse->get($key, ['count' => 0])['count'],
            ];
        }

        foreach ($inUse as $key => $row) {
            if (! isset($labels[$key])) {
                $labels[$key] = $row;
            }
        }

        return collect($labels)
            ->sortBy(fn (array $row) => strtolower($row['label']), SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * Suggestion labels only (for datalists / plain selects).
     *
     * @return list<string>
     */
    public function suggestionLabels(): array
    {
        return array_column($this->suggestions(), 'label');
    }
}
