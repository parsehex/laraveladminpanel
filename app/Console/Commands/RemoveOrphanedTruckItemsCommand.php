<?php

namespace App\Console\Commands;

use App\Enums\ItemType;
use App\Models\TruckAppliance;
use Illuminate\Console\Command;

class RemoveOrphanedTruckItemsCommand extends Command
{
    protected $signature = 'inventory:remove-orphaned-truck-items
        {--force : Soft-delete the items}';

    protected $description = 'Soft-delete items whose truck is already deleted';

    public function handle(): int
    {
        $items = TruckAppliance::query()
            ->with([
                'category:id,type',
                'truck' => fn ($query) => $query->withTrashed(),
            ])
            ->whereDoesntHave('truck')
            ->get(['id', 'truck_id', 'category_id']);

        if ($items->isEmpty()) {
            $this->info('No items on deleted trucks.');

            return self::SUCCESS;
        }

        $rows = $items
            ->groupBy(function (TruckAppliance $item): string {
                $type = $item->category?->type->value ?? ItemType::Appliance->value;

                return $item->truck_id.'|'.$type;
            })
            ->map(function ($group): array {
                /** @var TruckAppliance $item */
                $item = $group->first();
                $truck = $item->truck;

                return [
                    $truck?->name ?? 'Missing truck',
                    (string) $item->truck_id,
                    $truck?->deleted_at?->toDateTimeString() ?? '—',
                    $item->category?->type->value ?? ItemType::Appliance->value,
                    (string) $group->count(),
                ];
            })
            ->sortBy(fn (array $row): string => $row[1].$row[3])
            ->values()
            ->all();

        $this->table(['Truck', 'Truck id', 'Truck deleted at', 'Type', 'Items'], $rows);
        $this->line('Total: '.$items->count());

        if (! $this->option('force')) {
            $this->comment('Dry run. Re-run with --force to soft-delete these items.');

            return self::SUCCESS;
        }

        $deleted = TruckAppliance::query()->whereDoesntHave('truck')->delete();
        $this->info('Soft-deleted '.$deleted.' '.($deleted === 1 ? 'item' : 'items').'.');

        return self::SUCCESS;
    }
}
