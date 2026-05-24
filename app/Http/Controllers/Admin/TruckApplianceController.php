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

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="truck-'.$truck->id.'-appliances.csv"',
        ];

        return response()->streamDownload(function () use ($truck) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['category', 'model', 'serial_number', 'brand', 'product_name', 'msrp', 'receiving_condition', 'total_parts_cost', 'status']);

            foreach ($truck->appliances as $appliance) {
                fputcsv($handle, [
                    $appliance->category?->name,
                    $appliance->model?->model_number,
                    $appliance->serial_number,
                    $appliance->brand,
                    $appliance->product_name,
                    $appliance->msrp,
                    $appliance->receiving_condition,
                    $appliance->total_parts_cost,
                    $appliance->status,
                ]);
            }

            fclose($handle);
        }, 'truck-'.$truck->id.'-appliances.csv', $headers);
    }

    public function import(Request $request, Truck $truck)
    {
        abort_unless($request->user()?->can('appliance.create'), 403);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);
        $imported = 0;

        DB::transaction(function () use ($handle, $headers, $truck, $request, &$imported) {
            while (($row = fgetcsv($handle)) !== false) {
                $item = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));

                if (! $item || collect($item)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $category = ! empty($item['category'])
                    ? Category::query()->where('name', trim((string) $item['category']))->first()
                    : null;
                $model = ! empty($item['model'])
                    ? ApplianceModel::query()->where('model_number', trim((string) $item['model']))->first()
                    : null;
                $status = trim((string) ($item['status'] ?? ''));

                validator([
                    'msrp' => $item['msrp'] ?? 0,
                    'total_parts_cost' => $item['total_parts_cost'] ?? 0,
                    'receiving_condition' => $item['receiving_condition'] ?? null,
                    'status' => $status ?: null,
                ], [
                    'msrp' => ['required', 'numeric', 'min:0'],
                    'total_parts_cost' => ['required', 'numeric', 'min:0'],
                    'receiving_condition' => ['nullable', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
                    'status' => ['nullable', Rule::in(\App\Http\Controllers\Admin\InventoryController::STATUSES)],
                ])->validate();

                $this->syncBrand($item['brand'] ?? null, $request->user()->id);

                $truck->appliances()->create([
                    'category_id' => $category?->id,
                    'model_id' => $model?->id,
                    'serial_number' => trim((string) ($item['serial_number'] ?? '')) ?: null,
                    'brand' => trim((string) ($item['brand'] ?? '')) ?: null,
                    'product_name' => trim((string) ($item['product_name'] ?? '')) ?: null,
                    'msrp' => (float) ($item['msrp'] ?? 0),
                    'receiving_condition' => trim((string) ($item['receiving_condition'] ?? '')) ?: null,
                    'total_parts_cost' => (float) ($item['total_parts_cost'] ?? 0),
                    'status' => $status ?: null,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                $imported++;
            }
        });

        fclose($handle);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __("Imported {$imported} appliance(s)."));
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
}
