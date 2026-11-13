<?php

namespace App\Support;

use App\Models\Model as ApplianceModel;
use App\Models\TruckAppliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ItemCheckInMatcher
{
    public const LIMIT = 25;

    /**
     * @param  array{brand?: ?string, model_number?: ?string, product_name?: ?string, serial_number?: ?string}  $extraction
     * @return array{kind: string, known_model: bool, truncated: bool, units: Collection<int, TruckAppliance>}
     */
    public function match(array $extraction): array
    {
        $serial = LabelIdentifier::normalize((string) ($extraction['serial_number'] ?? ''));
        $modelNumber = LabelIdentifier::normalize((string) ($extraction['model_number'] ?? ''));
        $knownModel = $modelNumber !== '' && LabelIdentifier::matchingModel($modelNumber) !== null;

        if ($serial !== '') {
            $serialHits = $this->serialHits($serial);

            if ($serialHits->isNotEmpty()) {
                return [
                    'kind' => 'serial',
                    'known_model' => $knownModel,
                    'truncated' => false,
                    'units' => $serialHits,
                ];
            }
        }

        if ($modelNumber !== '') {
            $modelHits = $this->modelHits($modelNumber);

            if ($modelHits->isNotEmpty()) {
                return [
                    'kind' => 'model',
                    'known_model' => true,
                    'truncated' => $modelHits->count() > self::LIMIT,
                    'units' => $modelHits->take(self::LIMIT)->values(),
                ];
            }
        }

        return [
            'kind' => 'none',
            'known_model' => $knownModel,
            'truncated' => false,
            'units' => collect(),
        ];
    }

    /**
     * @return Collection<int, TruckAppliance>
     */
    private function serialHits(string $serial): Collection
    {
        return TruckAppliance::query()
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->with(['truck:id,name', 'model:id,model_number'])
            ->get(['id', 'truck_id', 'model_id', 'unit_label', 'serial_number', 'brand', 'product_name', 'status', 'checked_in_at'])
            ->filter(fn (TruckAppliance $unit): bool => LabelIdentifier::sameSerial($unit->serial_number, $serial))
            ->sortBy([
                fn (TruckAppliance $unit): string => (string) $unit->truck?->name,
                fn (TruckAppliance $unit): string => (string) $unit->unit_label,
            ])
            ->values();
    }

    /**
     * @return Collection<int, TruckAppliance>
     */
    private function modelHits(string $modelNumber): Collection
    {
        $modelIds = ApplianceModel::query()
            ->orderBy('id')
            ->get(['id', 'model_number'])
            ->filter(fn (ApplianceModel $candidate): bool => LabelIdentifier::normalize((string) $candidate->model_number) === $modelNumber)
            ->pluck('id');

        if ($modelIds->isEmpty()) {
            return collect();
        }

        return TruckAppliance::query()
            ->select('truck_appliances.*')
            ->join('trucks', function ($join): void {
                $join->on('trucks.id', '=', 'truck_appliances.truck_id')
                    ->whereNull('trucks.deleted_at');
            })
            ->whereIn('truck_appliances.model_id', $modelIds)
            ->whereNull('truck_appliances.checked_in_at')
            ->where(function (Builder $query): void {
                $query->whereNull('truck_appliances.status')
                    ->orWhereNotIn('truck_appliances.status', ['Sold', 'Scrap']);
            })
            ->with(['truck:id,name', 'model:id,model_number'])
            ->orderBy('trucks.name')
            ->orderBy('truck_appliances.unit_label')
            ->limit(self::LIMIT + 1)
            ->get();
    }
}
