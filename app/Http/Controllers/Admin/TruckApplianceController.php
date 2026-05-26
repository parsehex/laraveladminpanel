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
        $data = $request->validated();
        abort_unless((int) $data['truck_id'] === $truck->id, 403);

        $data['unit_label'] = isset($data['unit_label']) ? $this->nextUnitLabel($truck) : '';
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $truck->appliances()->create($data);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance added successfully.'));
    }

    public function update(UpdateTruckApplianceRequest $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($appliance->truck_id === $truck->id, 404);

        $data = $request->validated();
        abort_unless((int) $data['truck_id'] === $truck->id, 403);

        $data['unit_label'] = isset($data['unit_label']) ? ($appliance->unit_label ?: $this->nextUnitLabel($truck)) : '';
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $appliance->update($data);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance updated successfully.'));
    }

    public function destroy(Request $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.delete'), 403);
        abort_unless($appliance->truck_id === $truck->id, 404);

        $appliance->delete();

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance removed successfully.'));
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
            fputcsv($handle, ['Unit Label', 'Category', 'Brand', 'Model #', 'Product Name', 'Quantity', 'Our Cost', 'Serial #', 'Receiving Condition', 'MSRP', 'Fuel Type']);
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
        fgetcsv($handle);
        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($handle, $truck, $request, &$imported, &$updated) {
            $nextUnitNumber = $this->maxUnitNumber($truck) + 1;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 11 || collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $unitLabel = trim((string) ($row[0] ?? ''));
                if ($unitLabel === '') {
                    $unitLabel = $this->formatUnitLabel($truck, $nextUnitNumber);
                    $nextUnitNumber++;
                }
                $categoryName = trim((string) ($row[1] ?? ''));
                $brand = trim((string) ($row[2] ?? ''));
                $modelNumber = $this->normalizeIdentifier((string) ($row[3] ?? ''));
                $productName = trim((string) ($row[4] ?? ''));
                $quantity = (int) ($row[5] ?? 0);
                $ourCost = (float) ($row[6] ?? 0);
                $serialNumber = $this->normalizeIdentifier((string) ($row[7] ?? ''));
                $receivingCondition = trim((string) ($row[8] ?? ''));
                $msrp = (float) ($row[9] ?? 0);
                $fuelType = trim((string) ($row[10] ?? ''));

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
                ], [
                    'msrp' => ['required', 'numeric', 'min:0'],
                    'receiving_condition' => ['nullable', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
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
}
