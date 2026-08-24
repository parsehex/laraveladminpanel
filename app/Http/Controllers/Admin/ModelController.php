<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModelRequest;
use App\Http\Requests\UpdateModelRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Model;
use App\Models\Part;
use App\Models\UserAction;
use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:models.view')->only('index');
        $this->middleware('permission:models.create')->only('store');
        $this->middleware('permission:models.create')->only('importScraped');
        $this->middleware('permission:models.edit')->only('update');
        $this->middleware('permission:models.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Model::class);

        $query = Model::query()
            ->with([
                'category',
                'relatedParts' => fn ($query) => $query->orderBy('part_number'),
            ])
            ->withCount('relatedParts')
            ->latest();

        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($query) use ($search) {
                $query->whereLike('model_number', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereLike('brand', '%'.$search.'%');
            });
        }

        $models = PageSize::paginate($query, $request);
        $categoryIds = $models->getCollection()
            ->pluck('category_id')
            ->filter()
            ->when($request->filled('category'), fn ($ids) => $ids->push((int) $request->get('category')))
            ->unique()
            ->values();
        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get();

        return view('admin.models.index', compact('models', 'categories'));
    }

    public function store(StoreModelRequest $request)
    {
        $this->authorize('create', Model::class);

        $data = $request->validated();
        $data['msrp'] = number_format((float) ($data['msrp'] ?? 0), 2, '.', '');
        $data['status'] = 1;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $model = Model::create($data);

        UserAction::log('add_model', null, [
            'model_id' => $model->id,
            'model_number' => $model->model_number,
            'category' => $model->category_id,
        ]);

        return redirect()->route('admin.models.index')->with('success', __('Model created successfully.'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Model::class);

        $query = Model::query()
            ->with('category')
            ->leftJoin('categories', 'categories.id', '=', 'models.category_id')
            ->select('models.*')
            ->orderBy('categories.name')
            ->orderBy('models.brand')
            ->orderBy('models.model_number');

        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->whereLike('model_number', '%'.$search.'%');
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Model #', 'Product Name', 'Brand', 'Category', 'MSRP', 'Created At']);

            foreach ($query->get() as $model) {
                fputcsv($handle, [
                    $model->id,
                    $model->model_number,
                    $model->product_name,
                    $model->brand,
                    $model->category?->name,
                    $model->msrp,
                    $model->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, 'models_backup_'.now()->format('Y-m-d_H-i-s').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function importScraped(Request $request)
    {
        $this->authorize('create', Model::class);

        $data = $request->validate([
            'base_model' => ['required', 'string', 'max:255'],
            'csv_files' => ['required', 'array'],
            'csv_files.*' => ['file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $baseModel = $this->normalizeIdentifier($data['base_model']);
        $variations = [];
        $partCount = 0;
        $fileErrors = [];
        $model = Model::firstOrCreate(
            ['model_number' => $baseModel, 'msrp' => '0.00'],
            [
                'variations' => [],
                'status' => 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        if ($model->wasRecentlyCreated) {
            UserAction::log('add_model', null, [
                'model_id' => $model->id,
                'model_number' => $model->model_number,
                'from_import' => true,
            ]);
        }

        foreach ($data['csv_files'] as $file) {
            $filename = $file->getClientOriginalName();
            $handle = fopen($file->getRealPath(), 'r');

            if (! $handle) {
                $fileErrors[] = "Failed to open {$filename}";
                continue;
            }

            $sampleLine = '';
            while (($line = fgets($handle)) !== false && trim($sampleLine) === '') {
                $sampleLine = $line;
            }
            rewind($handle);
            $delimiter = substr_count($sampleLine, ';') > substr_count($sampleLine, ',') ? ';' : ',';
            $headers = fgetcsv($handle, 0, $delimiter);

            if (empty($headers)) {
                $fileErrors[] = "No headers in {$filename}";
                fclose($handle);
                continue;
            }

            preg_match('/^(.*)-WCI\.csv$/i', $filename, $matches);
            $variation = $matches[1] ?? $baseModel.'-default';

            if (! preg_match('/^'.preg_quote($baseModel, '/').'(\d*)?$/', $variation)) {
                $fileErrors[] = "Filename '{$filename}' doesn't match base '{$baseModel}'";
            }

            $variations[] = $variation;
            $lastDiagram = '';
            $lastImage = '';

            DB::transaction(function () use ($handle, $delimiter, $request, $model, $variation, &$lastDiagram, &$lastImage, &$partCount) {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if (count($row) < 7 || trim(implode('', $row)) === '') {
                        continue;
                    }

                    $diagramName = trim((string) ($row[1] ?? '')) ?: $lastDiagram;
                    $lastDiagram = $diagramName ?: $lastDiagram;
                    $imageUrl = trim((string) ($row[2] ?? '')) ?: $lastImage;
                    $lastImage = $imageUrl ?: $lastImage;
                    $item = trim((string) ($row[3] ?? ''));
                    $item = $item === 'NI' || $item === '' ? null : $item;
                    $make = trim((string) ($row[4] ?? 'WCI')) ?: 'WCI';
                    $partNumber = $this->normalizeIdentifier((string) ($row[5] ?? ''));
                    $description = trim((string) ($row[6] ?? ''));

                    if ($partNumber === '' || $description === '') {
                        continue;
                    }

                    $part = Part::query()->where('part_number', $partNumber)->first();
                    $payload = [
                        'product_name' => $description,
                        'model_compatibility' => $model->model_number,
                        'diagram_name' => $diagramName ?: null,
                        'image_url' => $imageUrl ?: null,
                        'make' => $make,
                        'item' => $item,
                        'updated_by' => $request->user()->id,
                    ];

                    if ($part) {
                        $part->update($payload);
                    } else {
                        $part = Part::create([
                            'part_number' => $partNumber,
                            'total_stock' => 0,
                            'retail_price' => 0,
                            'your_price' => 0,
                            'cross_reference' => null,
                            'created_by' => $request->user()->id,
                            ...$payload,
                        ]);
                    }

                    $link = [
                        'model_id' => $model->id,
                        'part_id' => $part->id,
                        'variation' => $variation,
                    ];
                    $exists = DB::table('model_parts')->where($link)->exists();

                    if ($exists) {
                        DB::table('model_parts')->where($link)->update(['updated_at' => now()]);
                    } else {
                        DB::table('model_parts')->insert([
                            ...$link,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $partCount++;
                    }
                }
            });

            fclose($handle);
        }

        $model->update([
            'variations' => array_values(array_unique($variations)),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => ! empty($variations),
            'parts_added' => $partCount,
            'variations' => array_values(array_unique($variations)),
            'files_processed' => count($data['csv_files']) - count($fileErrors),
            'error_msg' => $fileErrors ? ' (Warnings: '.implode('; ', $fileErrors).')' : '',
            'error' => empty($variations) ? 'No valid data processed from any file' : null,
        ]);
    }

    public function update(UpdateModelRequest $request, Model $model)
    {
        $this->authorize('update', $model);

        $data = $request->validated();
        $data['msrp'] = number_format((float) ($data['msrp'] ?? 0), 2, '.', '');
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $model->update($data);

        return redirect()->route('admin.models.index')->with('success', __('Model updated successfully.'));
    }

    public function destroy(Request $request, Model $model)
    {
        $this->authorize('delete', $model);

        UserAction::log('delete_model', null, [
            'model_id' => $model->id,
            'model_number' => $model->model_number,
            'category' => $model->category_id,
        ]);

        $model->update(['deleted_by' => $request->user()->id]);
        $model->delete();

        return redirect()->route('admin.models.index')->with('success', __('Model deleted successfully.'));
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
}
