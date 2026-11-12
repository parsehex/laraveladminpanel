<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplyInventoryStatusAutoLocationRequest;
use App\Http\Requests\Admin\ArchiveInventoryStatusRequest;
use App\Http\Requests\Admin\BulkUpdateInventoryStatusesRequest;
use App\Http\Requests\Admin\StoreInventoryStatusRequest;
use App\Models\InventoryStatus;
use App\Models\TruckAppliance;
use App\Support\InventoryStatusAppearance;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryStatusController extends Controller
{
    private const APPLY_LOCATION_CONFIRM_LIST_LIMIT = 15;

    public function __construct()
    {
        $this->middleware('permission:inventory-statuses.manage');
    }

    public function index(): View
    {
        $itemCounts = TruckAppliance::query()
            ->onLiveTruck()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereNotNull('status')
            ->where('status', '<>', '')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $itemsNeedingLocationPreview = $this->itemsNeedingAutoLocation()
            ->orderBy('appliances.unit_label')
            ->orderBy('appliances.serial_number')
            ->orderBy('appliances.id')
            ->select([
                'appliances.id',
                'appliances.status',
                'appliances.unit_label',
                'appliances.serial_number',
                'appliances.location',
                'statuses.auto_location',
            ])
            ->get()
            ->groupBy('status');

        $itemsNeedingLocation = $itemsNeedingLocationPreview->map->count();

        $statuses = InventoryStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryStatus $status) use ($itemCounts, $itemsNeedingLocation, $itemsNeedingLocationPreview) {
                $status->item_count = (int) ($itemCounts[$status->name] ?? 0);
                $status->items_needing_location = (int) ($itemsNeedingLocation[$status->name] ?? 0);
                $status->apply_location_confirm = $status->items_needing_location > 0
                    ? $this->applyLocationConfirmMessage(
                        $status,
                        $itemsNeedingLocationPreview->get($status->name, collect()),
                    )
                    : null;

                return $status;
            });

        return view('admin.inventory-statuses.index', [
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreInventoryStatusRequest $request): RedirectResponse
    {
        InventoryStatus::query()->create([
            'name' => $request->validated('name'),
            'auto_location' => $request->validated('auto_location'),
            'color' => $request->validated('color'),
            'is_system' => false,
            'sort_order' => InventoryStatus::nextSortOrder(),
        ]);

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Status created successfully.'));
    }

    public function update(BulkUpdateInventoryStatusesRequest $request): RedirectResponse
    {
        /** @var array<string, array{auto_location: ?string, color: string}> $statuses */
        $statuses = $request->validated('statuses');

        DB::transaction(function () use ($statuses): void {
            foreach ($statuses as $id => $payload) {
                InventoryStatus::query()
                    ->active()
                    ->whereKey((int) $id)
                    ->update([
                        'auto_location' => $payload['auto_location'],
                        'color' => $payload['color'],
                    ]);
            }
        });

        InventoryStatusAppearance::flush();

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Statuses updated successfully.'));
    }

    public function archive(ArchiveInventoryStatusRequest $request, InventoryStatus $inventoryStatus): RedirectResponse
    {
        $inventoryStatus->update([
            'archived_at' => now(),
        ]);

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Status archived successfully.'));
    }

    public function applyAutoLocation(
        ApplyInventoryStatusAutoLocationRequest $request,
        InventoryStatus $inventoryStatus,
    ): RedirectResponse {
        $location = $inventoryStatus->auto_location;
        $updated = 0;

        DB::transaction(function () use ($inventoryStatus, $location, $request, &$updated): void {
            $updated = TruckAppliance::query()
                ->onLiveTruck()
                ->where('status', $inventoryStatus->name)
                ->where(function ($query) use ($location) {
                    $query->whereNull('location')
                        ->orWhere('location', '')
                        ->orWhereRaw('LOWER(TRIM(location)) <> ?', [strtolower(trim((string) $location))]);
                })
                ->update([
                    'location' => $location,
                    'updated_by' => $request->user()->id,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __(':count item(s) updated to :location.', [
                'count' => $updated,
                'location' => $location,
            ]));
    }

    /**
     * Live-truck appliances whose location does not already match their status auto-location.
     */
    private function itemsNeedingAutoLocation(): Builder
    {
        return DB::table('truck_appliances as appliances')
            ->join('inventory_statuses as statuses', 'statuses.name', '=', 'appliances.status')
            ->whereNull('appliances.deleted_at')
            ->whereNotNull('statuses.auto_location')
            ->where('statuses.auto_location', '<>', '')
            ->where(function ($query) {
                $query->whereNull('appliances.location')
                    ->orWhere('appliances.location', '')
                    ->orWhereRaw('LOWER(TRIM(appliances.location)) <> LOWER(TRIM(statuses.auto_location))');
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('trucks')
                    ->whereColumn('trucks.id', 'appliances.truck_id')
                    ->whereNull('trucks.deleted_at');
            });
    }

    /**
     * @param  Collection<int, object{id: int, unit_label: ?string, serial_number: ?string, location: ?string}>  $items
     */
    private function applyLocationConfirmMessage(InventoryStatus $status, Collection $items): string
    {
        $target = (string) $status->auto_location;
        $empty = $items->filter(function (object $item): bool {
            $location = trim((string) ($item->location ?? ''));

            return $location === '';
        });
        $occupied = $items->filter(function (object $item): bool {
            $location = trim((string) ($item->location ?? ''));

            return $location !== '';
        });

        $lines = [
            __('Update :count item(s) currently in :status to location “:location”?', [
                'count' => $items->count(),
                'status' => $status->name,
                'location' => $target,
            ]),
        ];

        if ($empty->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __(':count have no location.', ['count' => $empty->count()]);
        }

        if ($occupied->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __(':count already have a location and will be overwritten:', [
                'count' => $occupied->count(),
            ]);

            $listed = $occupied->take(self::APPLY_LOCATION_CONFIRM_LIST_LIMIT);
            foreach ($listed as $item) {
                $lines[] = '- '.$this->applianceConfirmLabel($item).': '.$item->location;
            }

            $remaining = $occupied->count() - $listed->count();
            if ($remaining > 0) {
                $lines[] = __('- ...and :count more', ['count' => $remaining]);
            }
        }

        return implode("\n", $lines);
    }

    private function applianceConfirmLabel(object $item): string
    {
        $unitLabel = trim((string) ($item->unit_label ?? ''));
        if ($unitLabel !== '') {
            return $unitLabel;
        }

        $serial = trim((string) ($item->serial_number ?? ''));
        if ($serial !== '') {
            return $serial;
        }

        return '#'.$item->id;
    }
}
