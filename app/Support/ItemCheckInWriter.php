<?php

namespace App\Support;

use App\Enums\ItemType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ItemCheckIn;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemCheckInWriter
{
    /**
     * @param  array{brand?: ?string, model_number?: ?string, product_name?: ?string, serial_number?: ?string, category?: ?string, msrp?: mixed}  $fields
     * @return array{brand: ?string, model_number: ?string, product_name: ?string, serial_number: ?string, category: ?string, msrp: float}
     */
    public function fields(array $fields): array
    {
        $modelNumber = LabelIdentifier::normalize((string) ($fields['model_number'] ?? ''));
        $serialNumber = LabelIdentifier::normalize((string) ($fields['serial_number'] ?? ''));
        $category = trim((string) ($fields['category'] ?? ''));

        return [
            'brand' => $this->blankToNull(trim((string) ($fields['brand'] ?? ''))),
            'model_number' => $modelNumber === '' ? null : $modelNumber,
            'product_name' => $this->blankToNull(trim((string) ($fields['product_name'] ?? ''))),
            'serial_number' => $serialNumber === '' ? null : $serialNumber,
            'category' => $category === '' ? null : $category,
            'msrp' => (float) ($fields['msrp'] ?? 0),
        ];
    }

    /**
     * @param  array{brand?: ?string, model_number?: ?string, product_name?: ?string, serial_number?: ?string}  $fields
     */
    public function rememberFields(ItemCheckIn $checkIn, array $fields): void
    {
        $this->guardOpen($checkIn);
        $normalized = $this->fields($fields);

        $checkIn->update([
            'extraction' => [
                'brand' => $normalized['brand'],
                'model_number' => $normalized['model_number'],
                'product_name' => $normalized['product_name'],
                'serial_number' => $normalized['serial_number'],
            ],
        ]);
    }

    /**
     * @param  array{brand?: ?string, model_number?: ?string, product_name?: ?string, serial_number?: ?string}  $fields
     */
    public function confirmExisting(ItemCheckIn $checkIn, TruckAppliance $appliance, User $user, array $fields): TruckAppliance
    {
        $this->guardOpen($checkIn);
        $normalized = $this->fields($fields);

        return DB::transaction(function () use ($checkIn, $appliance, $user, $normalized): TruckAppliance {
            $updates = ['updated_by' => $user->id];

            if ($this->blankToNull(trim((string) $appliance->serial_number)) === null && $normalized['serial_number'] !== null) {
                $updates['serial_number'] = $normalized['serial_number'];
            }

            if ($this->blankToNull(trim((string) $appliance->brand)) === null && $normalized['brand'] !== null) {
                $updates['brand'] = $normalized['brand'];
            }

            if ($this->blankToNull(trim((string) $appliance->product_name)) === null && $normalized['product_name'] !== null) {
                $updates['product_name'] = $normalized['product_name'];
            }

            if ($appliance->model_id === null && $normalized['model_number'] !== null) {
                $model = LabelIdentifier::matchingModel($normalized['model_number']);

                if ($model !== null) {
                    $updates['model_id'] = $model->id;
                }
            }

            $updates['photos'] = array_values(array_unique([
                ...($appliance->photos ?? []),
                ...($checkIn->photos ?? []),
            ]));

            if ($appliance->checked_in_at === null) {
                $updates['checked_in_at'] = now();
                $updates['checked_in_by'] = $user->id;
            }

            $appliance->update($updates);
            $this->close($checkIn, $appliance, $user);

            return $appliance;
        });
    }

    /**
     * @param  array{brand?: ?string, model_number?: ?string, product_name?: ?string, serial_number?: ?string, category?: ?string, msrp?: mixed}  $fields
     */
    public function addUnit(ItemCheckIn $checkIn, Truck $truck, User $user, array $fields): TruckAppliance
    {
        $this->guardOpen($checkIn);
        $normalized = $this->fields($fields);

        return DB::transaction(function () use ($checkIn, $truck, $user, $normalized): TruckAppliance {
            $category = $this->resolveCategory($normalized['category'], $user);
            $model = $this->resolveModel(
                $normalized['model_number'],
                $normalized['msrp'],
                $normalized['product_name'],
                $normalized['brand'],
                $category?->id,
                $user->id,
            );

            $this->syncBrand($normalized['brand'], $user->id);

            $appliance = $truck->appliances()->create([
                'unit_label' => TruckUnitLabels::nextLabels($truck, 1)[0],
                'category_id' => $category?->id,
                'model_id' => $model?->id,
                'serial_number' => $normalized['serial_number'],
                'brand' => $normalized['brand'],
                'product_name' => $normalized['product_name'],
                'quantity' => 1,
                'price' => 0,
                'msrp' => $normalized['msrp'],
                'photos' => array_values($checkIn->photos ?? []),
                'checked_in_at' => now(),
                'checked_in_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $appliance->syncFurnitureDetails();
            TruckPricing::recalculate($truck);

            UserAction::log('create_appliance', $appliance->id, [
                'truck_id' => $truck->id,
                'model_number' => $normalized['model_number'],
                'from_check_in' => true,
            ]);

            $this->close($checkIn, $appliance, $user);

            return $appliance;
        });
    }

    private function guardOpen(ItemCheckIn $checkIn): void
    {
        if ($checkIn->confirmed_at !== null) {
            throw ValidationException::withMessages([
                'check_in' => 'This check-in was already confirmed.',
            ]);
        }
    }

    private function close(ItemCheckIn $checkIn, TruckAppliance $appliance, User $user): void
    {
        $checkIn->update([
            'truck_appliance_id' => $appliance->id,
            'confirmed_at' => now(),
        ]);

        UserAction::log('check_in', $appliance->id, [
            'check_in_id' => $checkIn->id,
            'truck_id' => $appliance->truck_id,
        ], $user);
    }

    private function resolveCategory(?string $name, User $user): ?Category
    {
        if ($name === null) {
            return null;
        }

        $category = Category::query()->where('name', $name)->first();

        if ($category !== null) {
            return $category;
        }

        abort_unless($user->can('category.create'), 403);

        return Category::query()->create([
            'name' => $name,
            'type' => ItemType::Furniture,
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function resolveModel(?string $modelNumber, float $msrp, ?string $productName, ?string $brand, ?int $categoryId, int $userId): ?ApplianceModel
    {
        if ($modelNumber === null) {
            return null;
        }

        $model = LabelIdentifier::matchingModel($modelNumber, $msrp);

        if ($model !== null) {
            $updates = ['updated_by' => $userId];

            if (! $model->product_name && $productName) {
                $updates['product_name'] = $productName;
            }

            if (! $model->brand && $brand) {
                $updates['brand'] = $brand;
            }

            if (! $model->category_id && $categoryId) {
                $updates['category_id'] = $categoryId;
            }

            if (count($updates) > 1) {
                $model->update($updates);
            }

            return $model;
        }

        $model = ApplianceModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => $productName,
            'brand' => $brand,
            'category_id' => $categoryId,
            'msrp' => number_format($msrp, 2, '.', ''),
            'status' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        UserAction::log('add_model', null, [
            'model_id' => $model->id,
            'model_number' => $modelNumber,
            'category_id' => $categoryId,
            'from_check_in' => true,
        ]);

        return $model;
    }

    private function syncBrand(?string $brand, int $userId): void
    {
        if ($brand === null) {
            return;
        }

        Brand::query()->firstOrCreate(
            ['name' => $brand],
            [
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function blankToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
