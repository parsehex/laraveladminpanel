<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckApplianceRequest;
use App\Http\Requests\UpdateTruckApplianceRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TruckApplianceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:appliance.create')->only('store');
        $this->middleware('permission:appliance.edit')->only('update');
        $this->middleware('permission:appliance.delete')->only('destroy');
        $this->middleware('permission:appliance.create')->only('import');
        $this->middleware('permission:trucks.view')->only('export');
    }

    public function store(StoreTruckApplianceRequest $request, Truck $truck)
    {
        $data = $this->legacyPayload($request, $truck);

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $truck->appliances()->create([
            ...$data,
            'unit_label' => $this->nextUnitLabel($truck),
            'quantity' => 1,
            'price' => 0,
            'photos' => [],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->recalculatePrices($truck);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance added successfully.'));
    }

    public function update(UpdateTruckApplianceRequest $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($appliance->truck_id === $truck->id, 404);

        $data = $this->legacyPayload($request, $truck);
        $data['price'] = 0;
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $appliance->update($data);
        $this->recalculatePrices($truck);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance updated successfully.'));
    }

    public function destroy(Request $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.delete'), 403);
        abort_unless($appliance->truck_id === $truck->id, 404);

        $appliance->delete();
        $this->recalculatePrices($truck);
        $this->renumberLabels($truck);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance removed successfully.'));
    }

    public function setCostPercent(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'cost_percent' => ['required', 'numeric'],
            'apply_all' => ['nullable'],
        ]);

        $percent = (float) $data['cost_percent'] / 100;

        if ($request->has('apply_all')) {
            abort_unless($request->user()?->hasRole('admin') || $request->user()?->role === 'admin', 403);

            Truck::query()->with('appliances')->each(function (Truck $truck) use ($percent) {
                foreach ($truck->appliances as $appliance) {
                    $appliance->update(['price' => (float) $appliance->msrp * $percent]);
                }
            });

            return redirect()->route('admin.trucks.show', $truck)->with('success', 'Cost % applied to all trucks.');
        }

        foreach ($truck->appliances as $appliance) {
            $appliance->update(['price' => (float) $appliance->msrp * $percent]);
        }

        return redirect()->route('admin.trucks.show', $truck)->with('success', 'Cost % applied to this truck.');
    }

    public function export(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('trucks.view'), 403);

        $truck->load(['appliances.category', 'appliances.model']);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $truck->name ?: 'unknown_truck');

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return response()->streamDownload(function () use ($truck) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Unit Label', 'Category', 'Brand', 'Model #', 'Product Name', 'Quantity', 'Our Cost', 'Serial #', 'Receiving Condition', 'MSRP', 'Fuel Type', 'Status', 'Total Parts Cost']);
            $fallbackUnitNumber = $this->maxUnitNumber($truck) + 1;

            $appliances = $truck->appliances->sortBy(function (TruckAppliance $appliance) {
                preg_match('/(\d+)$/', (string) $appliance->unit_label, $matches);

                return isset($matches[1]) ? (int) $matches[1] : PHP_INT_MAX;
            });

            foreach ($appliances as $appliance) {
                $unitLabel = $appliance->unit_label;
                if (! $unitLabel) {
                    $unitLabel = $this->formatUnitLabel($truck, $fallbackUnitNumber);
                    $fallbackUnitNumber++;
                }

                fputcsv($handle, [
                    $unitLabel,
                    $appliance->category?->name ?? '',
                    $appliance->brand,
                    $appliance->model?->model_number ?? '',
                    $appliance->product_name,
                    $appliance->quantity ?? 1,
                    $appliance->price,
                    $appliance->serial_number,
                    $appliance->receiving_condition,
                    $appliance->msrp,
                    $appliance->fuel_type,
                    $appliance->status,
                    $appliance->total_parts_cost,
                ]);
            }

            fclose($handle);
        }, $safeName.'_appliances.csv', $headers);
    }

    public function import(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $columns = $this->csvColumns($headers);
        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($handle, $columns, $truck, $request, &$imported, &$updated) {
            $nextUnitNumber = $this->maxUnitNumber($truck) + 1;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 11 || collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $unitLabel = trim((string) $this->csvValue($row, $columns, ['unit_label'], 0));
                if ($unitLabel === '') {
                    $unitLabel = $this->formatUnitLabel($truck, $nextUnitNumber);
                    $nextUnitNumber++;
                }
                $categoryName = trim((string) $this->csvValue($row, $columns, ['category'], 1));
                $brand = trim((string) $this->csvValue($row, $columns, ['brand'], 2));
                $modelNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['model', 'model_number', 'model_'], 3));
                $productName = trim((string) $this->csvValue($row, $columns, ['product_name', 'product'], 4));
                $quantity = (int) $this->csvValue($row, $columns, ['quantity'], 5);
                $ourCost = (float) $this->csvValue($row, $columns, ['our_cost', 'cost'], 6);
                $serialNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['serial', 'serial_number'], 7));
                $receivingCondition = trim((string) $this->csvValue($row, $columns, ['receiving_condition'], 8));
                $msrp = (float) $this->csvValue($row, $columns, ['msrp'], 9);
                $fuelType = trim((string) $this->csvValue($row, $columns, ['fuel_type'], 10));
                $status = trim((string) $this->csvValue($row, $columns, ['status'], null));
                $totalPartsCost = (float) ($this->csvValue($row, $columns, ['total_parts_cost', 'parts_cost'], null) ?? 0);

                $category = $categoryName !== ''
                    ? Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['status' => 1, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]
                    )
                    : null;
                $model = $modelNumber !== ''
                    ? ApplianceModel::firstOrCreate(
                        ['model_number' => $modelNumber],
                        [
                            'product_name' => $productName ?: null,
                            'brand' => $brand ?: null,
                            'category_id' => $category?->id,
                            'msrp' => $msrp,
                            'status' => 1,
                            'created_by' => $request->user()->id,
                            'updated_by' => $request->user()->id,
                        ]
                    )
                    : null;

                validator([
                    'msrp' => $msrp,
                    'receiving_condition' => $receivingCondition ?: null,
                    'status' => $status ?: null,
                    'total_parts_cost' => $totalPartsCost,
                ], [
                    'msrp' => ['required', 'numeric', 'min:0'],
                    'receiving_condition' => ['nullable', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
                    'status' => ['nullable', Rule::in(InventoryController::STATUSES)],
                    'total_parts_cost' => ['nullable', 'numeric', 'min:0'],
                ])->validate();

                $this->syncBrand($brand, $request->user()->id);

                $payload = [
                    'unit_label' => $unitLabel ?: null,
                    'category_id' => $category?->id,
                    'model_id' => $model?->id,
                    'serial_number' => $serialNumber ?: null,
                    'brand' => $brand ?: null,
                    'product_name' => $productName ?: null,
                    'quantity' => $quantity,
                    'price' => $ourCost,
                    'msrp' => $msrp,
                    'fuel_type' => $fuelType ?: null,
                    'receiving_condition' => $receivingCondition ?: null,
                    'status' => $status ?: null,
                    'total_parts_cost' => $totalPartsCost,
                    'updated_by' => $request->user()->id,
                ];

                $existing = $serialNumber !== ''
                    ? $truck->appliances()->where('serial_number', $serialNumber)->first()
                    : null;

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $truck->appliances()->create([...$payload, 'created_by' => $request->user()->id]);
                    $imported++;
                }
            }
        });

        fclose($handle);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __("Import successful! Added {$imported}, updated {$updated}."));
    }

    private function syncBrand(?string $brand, int $userId): void
    {
        $brand = trim((string) $brand);

        if ($brand === '') {
            return;
        }

        Brand::firstOrCreate(
            ['name' => $brand],
            [
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function legacyPayload(Request $request, Truck $truck): array
    {
        $data = $request->validated();
        abort_unless((int) $data['truck_id'] === $truck->id, 403);

        $categoryName = trim((string) $data['category']);
        $modelNumber = $this->normalizeIdentifier((string) $data['model_number']);
        $brand = trim((string) $data['brand']);
        $productName = trim((string) ($data['product_name'] ?? ''));
        $msrp = (float) ($data['msrp'] ?? 0);

        $category = Category::query()->where('name', $categoryName)->first();

        if (! $category) {
            abort_unless($request->user()?->can('category.create'), 403);

            $category = Category::create([
                'name' => $categoryName,
                'status' => 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        $model = ApplianceModel::firstOrCreate(
            ['model_number' => $modelNumber],
            [
                'product_name' => $productName ?: null,
                'brand' => $brand ?: null,
                'category_id' => $category->id,
                'msrp' => $msrp,
                'status' => 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        $fuelType = in_array($categoryName, ['Ranges', 'Dryers'], true)
            ? trim((string) ($data['fuel_type'] ?? 'N/A'))
            : 'N/A';

        return [
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'subcategory' => trim((string) ($data['subcategory'] ?? '')) ?: null,
            'model_id' => $model->id,
            'serial_number' => $this->normalizeIdentifier((string) $data['serial_number']),
            'brand' => $brand,
            'product_name' => $productName ?: null,
            'msrp' => $msrp,
            'receiving_condition' => $data['receiving_condition'],
            'fuel_type' => $fuelType ?: 'N/A',
            'total_parts_cost' => (float) ($data['total_parts_cost'] ?? 0),
            'original_order_number' => trim((string) ($data['original_order_number'] ?? '')) ?: null,
            'return_reason' => trim((string) ($data['return_reason'] ?? '')) ?: null,
            'return_problems' => trim((string) ($data['return_problems'] ?? '')) ?: null,
        ];
    }

    private function recalculatePrices(Truck $truck): void
    {
        $items = $truck->appliances()->get(['id', 'msrp']);
        $totalMsrp = (float) $items->sum('msrp');

        if ($totalMsrp > 0) {
            $percentage = (float) $truck->cost_of_truck / $totalMsrp;

            foreach ($items as $item) {
                $item->update(['price' => $percentage * (float) $item->msrp]);
            }

            return;
        }

        $count = $items->count();
        if ($count > 0) {
            $price = (float) $truck->cost_of_truck / $count;
            foreach ($items as $item) {
                $item->update(['price' => $price]);
            }
        }
    }

    private function renumberLabels(Truck $truck): void
    {
        $number = 1;
        $truck->appliances()
            ->orderBy('id')
            ->get()
            ->each(function (TruckAppliance $appliance) use ($truck, &$number) {
                $appliance->update(['unit_label' => $this->formatUnitLabel($truck, $number)]);
                $number++;
            });
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
    }

    private function nextUnitLabel(Truck $truck): string
    {
        return $this->formatUnitLabel($truck, $this->maxUnitNumber($truck) + 1);
    }

    private function maxUnitNumber(Truck $truck): int
    {
        return $truck->appliances()
            ->pluck('unit_label')
            ->map(function (?string $label) {
                preg_match('/(\d+)$/', (string) $label, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })
            ->max() ?? 0;
    }

    private function formatUnitLabel(Truck $truck, int $number): string
    {
        return trim((string) $truck->name).'-'.sprintf('%03d', $number);
    }

    private function csvColumns(array $headers): array
    {
        $columns = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/[^a-z0-9]+/', '_', $key);
            $columns[trim($key, '_')] = $index;
        }

        return $columns;
    }

    private function csvValue(array $row, array $columns, array $keys, ?int $fallbackIndex = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $columns)) {
                return $row[$columns[$key]] ?? null;
            }
        }

        return $fallbackIndex !== null ? ($row[$fallbackIndex] ?? null) : null;
    }
}
