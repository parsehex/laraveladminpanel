<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppliancePart;
use App\Models\InventoryStatusHistory;
use App\Models\Part;
use App\Models\Truck;
use App\Support\DataTable;
use App\Support\PageSize;
use App\Models\TruckAppliance;
use App\Testing\TestingFlowRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public const STATUSES = [
        'Triage',
        'Testing',
        'Repair',
        'Breakdown',
        'Demanufacture',
        'Cleaning',
        'Ready',
        'Scrap',
        'Show Room',
        'Quality Control QC',
        'Sold',
        'Holding for parts',
        'Holding',
    ];

    public function __construct()
    {
        $this->middleware('permission:inventory.view');
    }

    public function index(Request $request)
    {
        $dataTable = $this->inventoryDataTable();

        $query = TruckAppliance::query()
            ->with(['truck', 'category', 'model', 'statusHistories']);

        $this->applyFilters($query, $request);
        $dataTable->applySorting($query, $request);

        $limit = PageSize::resolve($request);

        if ($request->boolean('print')) {
            $printQuery = TruckAppliance::query()
                ->with(['truck', 'category', 'model', 'updater', 'parts.part', 'parts.user', 'statusHistories.user'])
                ->latest('id');

            if ($request->filled('ids')) {
                $ids = collect(explode(',', (string) $request->get('ids')))
                    ->map(fn ($id) => (int) trim($id))
                    ->filter()
                    ->values()
                    ->all();

                $printQuery->whereIn('id', $ids);
            } else {
                $this->applyFilters($printQuery, $request);

                if ($limit !== 'all') {
                    $page = max(1, (int) $request->get('page', 1));
                    $printQuery->skip(($page - 1) * $limit)->take($limit);
                }
            }

            return view('admin.inventory.print', [
                'items' => $printQuery->get(),
            ]);
        }

        $items = PageSize::paginate($query, $request);

        $brands = TruckAppliance::query()
            ->whereNotNull('brand')
            ->where('brand', '<>', '')
            ->selectRaw('MIN(brand) as brand')
            ->groupBy(DB::raw('LOWER(TRIM(brand))'))
            ->orderBy('brand')
            ->pluck('brand');

        $subcategories = TruckAppliance::query()
            ->whereNotNull('subcategory')
            ->where('subcategory', '<>', '')
            ->selectRaw('MIN(subcategory) as subcategory')
            ->groupBy(DB::raw('LOWER(TRIM(subcategory))'))
            ->orderBy('subcategory')
            ->pluck('subcategory');

        $categories = TruckAppliance::query()
            ->with('category:id,name')
            ->whereNotNull('category_id')
            ->get()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $inventoryData = collect();
        $totalInventoryValue = 0.0;
        $showAdminValue = $request->user()?->hasRole('admin') || $request->user()?->role === 'admin';

        if ($showAdminValue) {
            $statusExpression = "COALESCE(NULLIF(status, ''), 'Triage')";
            $costDate = $request->date('cost_date');

            $baseInventoryRows = DB::table('truck_appliances')
                ->selectRaw("$statusExpression as current_status")
                ->selectRaw('COALESCE(price, 0) as msrp')
                ->selectRaw("CASE WHEN $statusExpression IN ('Demanufacture', 'Scrap') THEN -COALESCE(total_parts_cost, 0) ELSE COALESCE(total_parts_cost, 0) END as total_parts_cost")
                ->whereNull('deleted_at');

            if ($costDate) {
                $endOfDate = $costDate->copy()->endOfDay();
                $latestStatusRows = DB::table('inventory_status_histories')
                    ->select('truck_appliance_id', 'status')
                    ->selectRaw('ROW_NUMBER() OVER(PARTITION BY truck_appliance_id ORDER BY created_at DESC) as row_number')
                    ->where('created_at', '<=', $endOfDate);

                $rankedStatusRows = DB::query()
                    ->fromSub($latestStatusRows, 'ranked_status')
                    ->where('row_number', 1);

                $baseInventoryRows = DB::table('truck_appliances')
                    ->joinSub($rankedStatusRows, 'latest_status', function ($join) {
                        $join->on('latest_status.truck_appliance_id', '=', 'truck_appliances.id');
                    })
                    ->selectRaw("latest_status.status as current_status")
                    ->selectRaw('COALESCE(truck_appliances.price, 0) as msrp')
                    ->selectRaw("CASE WHEN latest_status.status IN ('Demanufacture', 'Scrap') THEN -COALESCE(truck_appliances.total_parts_cost, 0) ELSE COALESCE(truck_appliances.total_parts_cost, 0) END as total_parts_cost")
                    ->whereNull('truck_appliances.deleted_at');
            }

            $inventoryData = DB::query()
                ->fromSub($baseInventoryRows, 'inventory_rows')
                ->select('current_status')
                ->selectRaw('COUNT(*) as unit_count')
                ->selectRaw('SUM(msrp) as total_base_cost')
                ->selectRaw('SUM(total_parts_cost) as total_parts_cost')
                ->selectRaw('SUM(msrp + total_parts_cost) as total_inventory_value')
                ->whereNotIn('current_status', ['Sold', 'Show Room'])
                ->groupBy('current_status')
                ->orderBy('current_status')
                ->get();

            $totalInventoryValue = (float) $inventoryData->sum('total_inventory_value');
        }

        return view('admin.inventory.index', [
            'items' => $items,
            'brands' => $brands,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'statuses' => self::STATUSES,
            'inventoryData' => $inventoryData,
            'totalInventoryValue' => $totalInventoryValue,
            'showAdminValue' => $showAdminValue,
            'dataTable' => $dataTable,
            ...$dataTable->sortState($request),
        ]);
    }

    public function stickers(Request $request)
    {
        $ids = collect(explode(',', (string) $request->get('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $items = TruckAppliance::query()
            ->with(['truck', 'model'])
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (TruckAppliance $item) => $ids->search($item->id))
            ->values();

        abort_if($items->isEmpty(), 404);

        return view('admin.inventory.stickers', compact('items'));
    }

    public function scan()
    {
        return view('admin.inventory.scan');
    }

    public function resolveScan(Request $request)
    {
        $validated = $request->validate([
            'qr_payload' => ['nullable', 'string', 'max:2048'],
            'model_number' => ['nullable', 'string', 'max:255'],
        ]);

        $applianceId = $this->parseApplianceIdFromQr($validated['qr_payload'] ?? null);
        $modelNumber = $this->normalizeModelNumber($validated['model_number'] ?? null);

        if ($applianceId && $modelNumber !== null) {
            $exact = TruckAppliance::query()
                ->with(['truck', 'model', 'category'])
                ->whereKey($applianceId)
                ->whereHas('model', function (Builder $query) use ($modelNumber) {
                    $query->whereRaw('LOWER(TRIM(model_number)) = ?', [strtolower($modelNumber)]);
                })
                ->first();

            if ($exact) {
                return response()->json([
                    'mode' => 'exact',
                    'scanned_id' => $applianceId,
                    'model_number' => $modelNumber,
                    'appliance' => $this->scanMatchPayload($exact),
                    'url' => route('admin.inventory.show', $exact),
                ]);
            }
        }

        if ($modelNumber !== null) {
            $matches = TruckAppliance::query()
                ->with(['truck', 'model', 'category'])
                ->whereHas('model', function (Builder $query) use ($modelNumber) {
                    $query->whereRaw('LOWER(TRIM(model_number)) = ?', [strtolower($modelNumber)]);
                })
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (TruckAppliance $appliance) => $this->scanMatchPayload($appliance))
                ->values();

            return response()->json([
                'mode' => 'suggestions',
                'scanned_id' => $applianceId,
                'model_number' => $modelNumber,
                'matches' => $matches,
            ]);
        }

        if ($applianceId) {
            $byId = TruckAppliance::query()
                ->with(['truck', 'model', 'category'])
                ->whereKey($applianceId)
                ->first();

            return response()->json([
                'mode' => 'need_model',
                'scanned_id' => $applianceId,
                'message' => $byId
                    ? 'Possible match from QR ID. Still scanning for the model barcode…'
                    : 'QR read. Point at the model barcode too.',
                'matches' => $byId ? [$this->scanMatchPayload($byId)] : [],
            ]);
        }

        return response()->json([
            'mode' => 'empty',
            'message' => 'No usable QR or model barcode detected.',
        ], 422);
    }

    public function destroy(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->role === 'admin', 403);

        $truck = $appliance->truck;
        $appliance->delete();

        if ($truck) {
            $this->recalculateTruckPrices($truck);
        }

        return back()->with('success', __('Appliance deleted from inventory.'));
    }

    public function show(TruckAppliance $appliance, TestingFlowRepository $flows)
    {
        $appliance->load([
            'truck',
            'category',
            'model',
            'statusHistories.user',
            'parts.part',
            'parts.user',
        ]);

        $testingResultLinks = $flows->mapResultLinksToTestingHistories(
            $appliance->id,
            $appliance->statusHistories,
        );

        return view('admin.inventory.show', [
            'appliance' => $appliance,
            'statuses' => self::STATUSES,
            'testingResultLinks' => $testingResultLinks,
            'testingResultCount' => count($flows->listResultsForAppliance($appliance->id)),
            'trucks' => Truck::query()->whereKeyNot($appliance->truck_id)->orderBy('name')->get(['id', 'name']),
            'locations' => TruckAppliance::query()
                ->whereNotNull('location')
                ->where('location', '<>', '')
                ->selectRaw('MIN(location) as location')
                ->selectRaw('COUNT(*) as usage_count')
                ->groupBy(DB::raw('LOWER(TRIM(location))'))
                ->orderBy('location')
                ->get()
                ->map(fn ($row) => [
                    'label' => $row->location,
                    'count' => (int) $row->usage_count,
                ])
                ->values(),
        ]);
    }

    public function searchParts(Request $request)
    {
        abort_unless($request->user()?->can('parts.view') || $request->user()?->can('appliance.edit'), 403);

        $search = $request->string('q')->trim();

        if ($search->length() < 2) {
            return response()->json([]);
        }

        $parts = Part::query()
            ->where(function (Builder $query) use ($search) {
                $query->whereLike('part_number', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereLike('model_compatibility', '%'.$search.'%')
                    ->orWhereLike('cross_reference', '%'.$search.'%');
            })
            ->orderBy('part_number')
            ->limit(10)
            ->get(['id', 'part_number', 'product_name', 'your_price', 'retail_price', 'total_stock']);

        return response()->json($parts->map(fn (Part $part) => [
            'id' => $part->id,
            'part_number' => $part->part_number,
            'description' => $part->product_name ?: $part->part_number,
            'cost' => (float) ($part->your_price ?: $part->retail_price ?: 0),
            'stock' => $part->total_stock,
            'label' => trim($part->part_number.' - '.($part->product_name ?: '')),
        ]));
    }

    public function updateLocation(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $appliance->update([
            'location' => $data['location'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Location updated successfully.'));
    }

    public function moveTruck(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'truck_id' => ['required', 'exists:trucks,id', Rule::notIn([$appliance->truck_id])],
        ]);

        $oldTruckName = $appliance->truck?->name ?? 'Unassigned';
        $newTruck = Truck::findOrFail($data['truck_id']);

        DB::transaction(function () use ($appliance, $newTruck, $oldTruckName, $request) {
            $appliance->update([
                'truck_id' => $newTruck->id,
                'updated_by' => $request->user()->id,
            ]);

            $appliance->statusHistories()->create([
                'status' => $appliance->status ?: 'Triage',
                'notes' => 'Moved from '.$oldTruckName.' to '.$newTruck->name.'.',
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('admin.inventory.show', $appliance)
            ->with('success', __('Unit moved to :truck successfully.', ['truck' => $newTruck->name]));
    }

    public function updateStatus(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
            'sold_price' => ['nullable', 'numeric', 'min:0'],
            'parts_ordered' => ['nullable', 'boolean'],
        ]);

        $update = [
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ];

        if ($data['status'] === 'Sold') {
            $update['location'] = null;
            $update['sold_price'] = $data['sold_price'] ?? null;
            $update['sold_by'] = $request->user()->name;
            $update['sold_at'] = now();
        }

        $appliance->update($update);
        $appliance->statusHistories()->create([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'parts_ordered' => (bool) ($data['parts_ordered'] ?? false),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', __('Status updated successfully.'));
    }

    public function storePart(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'part_id' => ['nullable', 'exists:parts,id'],
            'description' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        $selectedPart = ! empty($data['part_id'])
            ? Part::query()->find($data['part_id'])
            : null;

        DB::transaction(function () use ($appliance, $data, $request, $selectedPart) {
            $part = $selectedPart ?: Part::query()->create([
                'part_number' => $this->generatePartNumber(),
                'product_name' => $data['description'],
                'model_compatibility' => $appliance->model?->model_number,
                'total_stock' => 0,
                'retail_price' => $data['cost'],
                'your_price' => $data['cost'],
                'cross_reference' => null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $partCost = (float) ($part->your_price ?: $part->retail_price ?: $data['cost']);

            $appliance->parts()->create([
                'part_id' => $part->id,
                'description' => $part->product_name ?: $data['description'],
                'part_number' => $part->part_number,
                'cost' => $partCost,
                'source' => null,
                'user_id' => $request->user()->id,
            ]);

            $appliance->update([
                'total_parts_cost' => $appliance->parts()->sum('cost'),
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', __('Part added successfully.'));
    }

    public function uploadPhotos(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'photos' => ['required', 'array', 'max:5'],
            'photos.*' => ['required', 'image', 'max:5120'],
        ]);

        $photos = $appliance->photos ?? [];

        foreach ($data['photos'] as $photo) {
            $photos[] = $photo->store('appliance-photos/'.$appliance->id, 'public');
        }

        $appliance->update([
            'photos' => array_values($photos),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Photos uploaded successfully.'));
    }

    public function photos(Request $request, TruckAppliance $appliance)
    {
        $photos = collect($appliance->photos ?? [])
            ->filter(fn ($photo) => Storage::disk('public')->exists($photo))
            ->map(fn ($photo) => [
                'path' => $photo,
                'url' => route('admin.inventory.photos.show', ['appliance' => $appliance, 'photo' => $photo]),
            ])
            ->values();

        return response()->json([
            'appliance' => [
                'id' => $appliance->id,
                'unit_label' => $appliance->unit_label,
                'serial_number' => $appliance->serial_number,
            ],
            'photos' => $photos,
        ]);
    }

    public function showPhoto(Request $request, TruckAppliance $appliance)
    {
        $data = $request->validate([
            'photo' => ['required', 'string'],
        ]);

        $photos = collect($appliance->photos ?? []);
        abort_unless($photos->contains($data['photo']), 404);
        abort_unless(Storage::disk('public')->exists($data['photo']), 404);

        return Storage::disk('public')->response($data['photo']);
    }

    public function destroyPhoto(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);

        $data = $request->validate([
            'photo' => ['required', 'string'],
        ]);

        $photos = collect($appliance->photos ?? []);
        abort_unless($photos->contains($data['photo']), 404);

        Storage::disk('public')->delete($data['photo']);

        $appliance->update([
            'photos' => $photos->reject(fn ($photo) => $photo === $data['photo'])->values()->all(),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Photo deleted successfully.'));
    }

    public function destroyPart(Request $request, TruckAppliance $appliance, AppliancePart $part)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);
        abort_unless($part->truck_appliance_id === $appliance->id, 404);

        $part->delete();
        $appliance->update([
            'total_parts_cost' => $appliance->parts()->sum('cost'),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Part removed successfully.'));
    }

    private function generatePartNumber(): string
    {
        do {
            $number = (string) random_int(1000000000, 9999999999);
            $letters = Str::upper(Str::random(2));
            $partNumber = $number.$letters;
        } while (
            Part::query()->where('part_number', $partNumber)->exists()
            || AppliancePart::query()->where('part_number', $partNumber)->exists()
        );

        return $partNumber;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->trim();

            $query->where(function (Builder $query) use ($search) {
                $query->whereLike('serial_number', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereLike('location', '%'.$search.'%')
                    ->orWhereHas('model', fn (Builder $modelQuery) => $modelQuery->whereLike('model_number', '%'.$search.'%'));
            });
        }

        $statuses = collect($request->input('status', []))
            ->map(fn ($status) => trim((string) $status))
            ->filter()
            ->values();

        if ($statuses->isNotEmpty()) {
            $query->where(function (Builder $query) use ($statuses) {
                $explicitStatuses = $statuses->reject(fn ($status) => $status === 'Triage')->values();

                if ($explicitStatuses->isNotEmpty()) {
                    $query->whereIn('status', $explicitStatuses->all());
                }

                if ($statuses->contains('Triage')) {
                    $method = $explicitStatuses->isNotEmpty() ? 'orWhere' : 'where';
                    $query->{$method}(function (Builder $triageQuery) {
                        $triageQuery->whereNull('status')->orWhere('status', '')->orWhere('status', 'Triage');
                    });
                }
            });
        }

        if ($request->filled('brand')) {
            $query->whereLike('brand', '%'.$request->string('brand')->trim().'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('sub_category')) {
            $query->whereLike('subcategory', '%'.$request->string('sub_category')->trim().'%');
        }
    }

    private function inventoryDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'inventoryTableColumns',
            defaultSort: ['truck_appliances.id', 'desc'],
            columns: [
                [
                    'key' => 'truck',
                    'label' => 'Truck',
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->leftJoin('trucks', 'trucks.id', '=', 'truck_appliances.truck_id')
                        ->orderBy('trucks.name', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sort' => fn (Builder $query, string $direction) => $query->orderByRaw(
                        "COALESCE(NULLIF(truck_appliances.status, ''), 'Triage') ".$direction
                    ),
                ],
                [
                    'key' => 'unit_label',
                    'label' => 'Unit Label',
                    'truncate' => true,
                    'sort' => 'truck_appliances.unit_label',
                ],
                [
                    'key' => 'model',
                    'label' => 'Model #',
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->leftJoin('models', 'models.id', '=', 'truck_appliances.model_id')
                        ->orderBy('models.model_number', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'serial_number',
                    'label' => 'Serial #',
                    'sort' => 'truck_appliances.serial_number',
                ],
                [
                    'key' => 'brand',
                    'label' => 'Brand',
                    'truncate' => true,
                    'sort' => 'truck_appliances.brand',
                ],
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'truncate' => true,
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->leftJoin('categories', 'categories.id', '=', 'truck_appliances.category_id')
                        ->orderBy('categories.name', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'subcategory',
                    'label' => 'SubCategory',
                    'truncate' => true,
                    'sort' => 'truck_appliances.subcategory',
                ],
                [
                    'key' => 'location',
                    'label' => 'Location',
                    'truncate' => true,
                    'sort' => 'truck_appliances.location',
                ],
                [
                    'key' => 'status_date',
                    'label' => 'Status Date/Time',
                    'sort' => fn (Builder $query, string $direction) => $query->orderBy(
                        InventoryStatusHistory::query()
                            ->select('created_at')
                            ->whereColumn('truck_appliance_id', 'truck_appliances.id')
                            ->latest('created_at')
                            ->limit(1),
                        $direction
                    ),
                ],
                [
                    'key' => 'total_cost',
                    'label' => 'Total Cost',
                    'align' => 'right',
                    'sort' => fn (Builder $query, string $direction) => $query->orderByRaw(
                        '(COALESCE(truck_appliances.msrp, 0) + CASE WHEN COALESCE(truck_appliances.status, \'\') IN (\'Demanufacture\', \'Scrap\') THEN -COALESCE(truck_appliances.total_parts_cost, 0) ELSE COALESCE(truck_appliances.total_parts_cost, 0) END) '.$direction
                    ),
                ],
                [
                    'key' => 'sold_price',
                    'label' => 'Sold Price',
                    'align' => 'right',
                    'sort' => 'truck_appliances.sold_price',
                ],
            ],
        );
    }

    private function recalculateTruckPrices(Truck $truck): void
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

        if ($items->count() > 0) {
            $price = (float) $truck->cost_of_truck / $items->count();
            foreach ($items as $item) {
                $item->update(['price' => $price]);
            }
        }
    }

    private function parseApplianceIdFromQr(?string $payload): ?int
    {
        if ($payload === null) {
            return null;
        }

        $payload = trim($payload);

        if ($payload === '') {
            return null;
        }

        if (ctype_digit($payload)) {
            return (int) $payload;
        }

        if (preg_match('/[?&]id=(\d+)/i', $payload, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('#/(?:admin/)?inventory/(\d+)#i', $payload, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalizeModelNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strcasecmp($value, 'N/A') === 0) {
            return null;
        }

        return $value;
    }

    private function scanMatchPayload(TruckAppliance $appliance): array
    {
        return [
            'id' => $appliance->id,
            'unit_label' => $appliance->unit_label,
            'serial_number' => $appliance->serial_number,
            'brand' => $appliance->brand,
            'product_name' => $appliance->product_name,
            'status' => $appliance->status ?: 'Triage',
            'location' => $appliance->location,
            'model_number' => $appliance->model?->model_number,
            'truck_name' => $appliance->truck?->name,
            'category_name' => $appliance->category?->name,
            'url' => route('admin.inventory.show', $appliance),
        ];
    }

}
