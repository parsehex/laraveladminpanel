<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckApplianceRequest;
use App\Http\Requests\UpdateTruckApplianceRequest;
use App\Imports\TruckApplianceCsvImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\UserAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TruckApplianceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:appliance.create')->only('store');
        $this->middleware('permission:appliance.edit')->only('update');
        $this->middleware('permission:appliance.delete')->only('destroy');
        $this->middleware('permission:appliance.create')->only([
            'importPreview',
            'importReview',
            'importConfirm',
            'importCancel',
        ]);
        $this->middleware('permission:trucks.view')->only('export');
    }

    public function store(StoreTruckApplianceRequest $request, Truck $truck)
    {
        $data = $this->legacyPayload($request, $truck);

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $appliance = $truck->appliances()->create([
            ...$data,
            'unit_label' => $this->nextUnitLabel($truck),
            'quantity' => 1,
            'price' => 0,
            'photos' => [],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $appliance->syncFurnitureDetails();
        $this->recalculatePrices($truck);

        UserAction::log('create_appliance', $appliance->id, $data);

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
        $appliance->syncFurnitureDetails();
        $this->recalculatePrices($truck);

        UserAction::log('update_appliance', $appliance->id, $data);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance updated successfully.'));
    }

    public function destroy(Request $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.delete'), 403);
        abort_unless($appliance->truck_id === $truck->id, 404);

        UserAction::log('delete_appliance', $appliance->id, [
            'truck_id' => $truck->id,
            'photos_deleted' => count($appliance->photos ?? []),
        ]);

        $appliance->delete();
        $this->recalculatePrices($truck);

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
            abort_unless($request->user()?->can('appliance.cost-override'), 403);

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

        $truck->load(['appliances.category', 'appliances.model', 'appliances.parts']);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $truck->name ?: 'unknown_truck');

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return response()->streamDownload(function () use ($truck) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Unit Label', 'Category', 'Sub-Category', 'Brand', 'Model #', 'Product Name', 'Quantity', 'Our Cost', 'Serial #', 'Receiving Condition', 'MSRP', 'Fuel Type', 'Status', 'Total Parts Cost', 'Sold Price', 'Sold By', 'Sold Date']);
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
                    $appliance->subcategory,
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
                    $appliance->partsCost(),
                    $appliance->sold_price,
                    $appliance->sold_by,
                    $appliance->sold_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $safeName.'_appliances.csv', $headers);
    }

    public function importPreview(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $this->discardStagedImport($request);

        $token = (string) Str::uuid();
        $relativePath = 'private/csv-imports/'.$request->user()->id.'/'.$token.'.csv';
        Storage::disk('local')->put($relativePath, file_get_contents($data['csv_file']->getRealPath()));

        $request->session()->put($this->importSessionKey(), [
            'token' => $token,
            'truck_id' => $truck->id,
            'path' => $relativePath,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('admin.trucks.appliances.import.review', $truck);
    }

    public function importReview(Request $request, Truck $truck, TruckApplianceCsvImport $importer)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        try {
            $staged = $this->stagedImport($request, $truck);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.show', $truck)
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $absolutePath = Storage::disk('local')->path($staged['path']);
        $preview = $importer->preview($truck, $absolutePath, $request->user());

        return view('admin.trucks.appliances.import-review', [
            'truck' => $truck,
            'preview' => $preview,
        ]);
    }

    public function importConfirm(Request $request, Truck $truck, TruckApplianceCsvImport $importer)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        try {
            $staged = $this->stagedImport($request, $truck);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.show', $truck)
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $absolutePath = Storage::disk('local')->path($staged['path']);

        try {
            $preview = $importer->preview($truck, $absolutePath, $request->user());

            if (! $preview->canConfirm()) {
                return redirect()
                    ->route('admin.trucks.appliances.import.review', $truck)
                    ->with('error', __('Fix the CSV errors before confirming the import.'));
            }

            $result = $importer->commit($truck, $absolutePath, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.appliances.import.review', $truck)
                ->withErrors($exception->errors());
        }

        $this->discardStagedImport($request);

        return redirect()
            ->route('admin.trucks.show', $truck)
            ->with('success', __("Import successful! Added {$result->imported}, updated {$result->updated}."));
    }

    public function importCancel(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        $this->discardStagedImport($request);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Import cancelled.'));
    }

    /**
     * @return array{token: string, truck_id: int, path: string, expires_at: int}
     */
    private function stagedImport(Request $request, Truck $truck): array
    {
        $staged = $request->session()->get($this->importSessionKey());

        if (! is_array($staged)
            || (int) ($staged['truck_id'] ?? 0) !== $truck->id
            || empty($staged['path'])
            || empty($staged['expires_at'])
            || (int) $staged['expires_at'] < now()->timestamp
            || ! Storage::disk('local')->exists($staged['path'])) {
            $this->discardStagedImport($request);

            throw ValidationException::withMessages([
                'csv_file' => ['The staged import expired or was not found. Upload the CSV again.'],
            ]);
        }

        return $staged;
    }

    private function discardStagedImport(Request $request): void
    {
        $staged = $request->session()->pull($this->importSessionKey());

        if (is_array($staged) && ! empty($staged['path'])) {
            Storage::disk('local')->delete($staged['path']);
        }
    }

    private function importSessionKey(): string
    {
        return 'appliance_csv_import';
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
        $modelNumber = $this->normalizeIdentifier((string) ($data['model_number'] ?? ''));
        $brand = trim((string) $data['brand']);
        $productName = trim((string) ($data['product_name'] ?? ''));
        $msrp = (float) ($data['msrp'] ?? 0);

        $category = Category::query()->where('name', $categoryName)->first();

        if (! $category) {
            abort_unless($request->user()?->can('category.create'), 403);

            $category = Category::create([
                'name' => $categoryName,
                'type' => ItemType::Appliance,
                'status' => 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        $isFurniture = $category->isFurniture();
        $model = $isFurniture
            ? null
            : $this->resolveModel($modelNumber, $msrp, $productName, $brand, $category->id, $request->user()->id);

        $fuelType = ! $isFurniture && in_array($categoryName, ['Ranges', 'Dryers'], true)
            ? trim((string) ($data['fuel_type'] ?? 'N/A'))
            : 'N/A';

        $serialNumber = trim((string) ($data['serial_number'] ?? ''));

        return [
            'truck_id' => $truck->id,
            'category_id' => $category->id,
            'subcategory' => trim((string) ($data['subcategory'] ?? '')) ?: null,
            'model_id' => $model?->id,
            'serial_number' => $serialNumber === '' ? null : $this->normalizeIdentifier($serialNumber),
            'brand' => $brand,
            'product_name' => $productName ?: null,
            'msrp' => $msrp,
            'receiving_condition' => $data['receiving_condition'],
            'fuel_type' => $fuelType ?: 'N/A',
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

    private function resolveModel(string $modelNumber, float $msrp, ?string $productName, ?string $brand, ?int $categoryId, int $userId): ApplianceModel
    {
        $model = ApplianceModel::query()
            ->where('model_number', $modelNumber)
            ->get()
            ->first(fn (ApplianceModel $model) => abs((float) $model->msrp - $msrp) < 0.005);

        if ($model) {
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
            'product_name' => $productName ?: null,
            'brand' => $brand ?: null,
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
            'from_truck' => true,
        ]);

        return $model;
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
