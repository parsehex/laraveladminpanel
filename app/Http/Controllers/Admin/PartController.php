<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Models\Model;
use App\Models\Part;
use App\Support\DataTable;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:parts.view')->only('index');
        $this->middleware('permission:parts.create')->only('store');
        $this->middleware('permission:parts.edit')->only('update');
        $this->middleware('permission:parts.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Part::class);

        $dataTable = $this->partsIndexDataTable();
        $query = Part::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            if ($request->filled('is_from_model_section')) {
                $query->whereLike('model_compatibility', '%'.$search.'%');
            } else {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('part_number', '%'.$search.'%')
                        ->orWhereLike('product_name', '%'.$search.'%')
                        ->orWhereLike('model_compatibility', '%'.$search.'%')
                        ->orWhereLike('cross_reference', '%'.$search.'%');
                });
            }
        }

        $dataTable->applySorting($query, $request);

        $parts = $query->paginate(12)->withQueryString();
        $modelNumbers = $parts->getCollection()
            ->pluck('model_compatibility')
            ->filter()
            ->unique()
            ->values();
        $models = Model::query()
            ->whereIn('model_number', $modelNumbers)
            ->orderBy('model_number')
            ->get(['id', 'model_number', 'product_name']);

        return view('admin.parts.index', [
            'parts' => $parts,
            'models' => $models,
            'dataTable' => $dataTable,
            ...$dataTable->sortState($request),
        ]);
    }

    private function partsIndexDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'partsIndexTableColumns',
            defaultSort: [['parts.id', 'desc']],
            columns: [
                [
                    'key' => 'id',
                    'label' => 'Sr. No',
                    'sort' => 'parts.id',
                ],
                [
                    'key' => 'total_stock',
                    'label' => 'Total Stock',
                    'align' => 'right',
                    'sort' => 'parts.total_stock',
                ],
                [
                    'key' => 'part_number',
                    'label' => 'Part #',
                    'sort' => 'parts.part_number',
                ],
                [
                    'key' => 'product_name',
                    'label' => 'Product Name',
                    'truncate' => true,
                    'sort' => 'parts.product_name',
                ],
                [
                    'key' => 'model_compatibility',
                    'label' => 'Model Compatibility',
                    'truncate' => true,
                    'sort' => 'parts.model_compatibility',
                ],
                [
                    'key' => 'retail_price',
                    'label' => 'Retail Price',
                    'align' => 'right',
                    'sort' => 'parts.retail_price',
                ],
                [
                    'key' => 'your_price',
                    'label' => 'Your Price',
                    'align' => 'right',
                    'sort' => 'parts.your_price',
                ],
                [
                    'key' => 'cross_reference',
                    'label' => 'Cross Reference',
                    'truncate' => true,
                    'sort' => 'parts.cross_reference',
                ],
            ],
        );
    }

    public function store(StorePartRequest $request)
    {
        $this->authorize('create', Part::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $part = Part::withTrashed()->where('part_number', $data['part_number'])->first();

        if ($part?->trashed()) {
            $part->restore();
            $part->update($data);
        } else {
            Part::create($data);
        }

        return redirect()->route('admin.parts.index')->with('success', __('Part created successfully.'));
    }

    public function import(Request $request)
    {
        $this->authorize('create', Part::class);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $columns = $this->csvColumns($headers);
        $imported = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 7) {
                continue;
            }

            $partNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['part_number', 'partnumber'], 1));

            if ($partNumber === '') {
                continue;
            }

            $payload = validator([
                'part_number' => $partNumber,
                'product_name' => trim((string) $this->csvValue($row, $columns, ['product_name', 'product', 'name'], null)) ?: null,
                'model_compatibility' => trim((string) $this->csvValue($row, $columns, ['models_it_applies_to', 'model_compatibility', 'models'], 6)) ?: null,
                'total_stock' => 0,
                'retail_price' => $this->csvValue($row, $columns, ['retail_price', 'retail'], 2),
                'your_price' => $this->csvValue($row, $columns, ['your_price', 'cost'], 3),
                'cross_reference' => trim((string) $this->csvValue($row, $columns, ['cross_reference_information', 'cross_reference'], 5)) ?: null,
            ], [
                'part_number' => ['required', 'string', 'max:255'],
                'product_name' => ['nullable', 'string', 'max:255'],
                'model_compatibility' => ['nullable', 'string', 'max:255'],
                'total_stock' => ['nullable', 'integer', 'min:0'],
                'retail_price' => ['required', 'numeric', 'min:0'],
                'your_price' => ['required', 'numeric', 'min:0'],
                'cross_reference' => ['nullable', 'string', 'max:255'],
            ])->validate();

            $part = Part::withTrashed()->where('part_number', $payload['part_number'])->first();
            $payload['total_stock'] = $payload['total_stock'] ?? 0;
            $payload['updated_by'] = $request->user()->id;

            if ($part) {
                if ($part->trashed()) {
                    $part->restore();
                    $payload['created_by'] = $part->created_by ?: $request->user()->id;
                    $imported++;
                } else {
                    $updated++;
                }
                $part->update($payload);
            } else {
                $payload['created_by'] = $request->user()->id;
                Part::create($payload);
                $imported++;
            }
        }

        fclose($handle);

        return redirect()
            ->route('admin.parts.index')
            ->with('success', __("Imported {$imported} new part(s), updated {$updated} existing part(s)."));
    }

    public function update(UpdatePartRequest $request, Part $part)
    {
        $this->authorize('update', $part);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $part->update($data);

        return redirect()->route('admin.parts.index')->with('success', __('Part updated successfully.'));
    }

    public function destroy(Part $part)
    {
        $this->authorize('delete', $part);

        $part->delete();

        return redirect()->route('admin.parts.index')->with('success', __('Part deleted successfully.'));
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
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
