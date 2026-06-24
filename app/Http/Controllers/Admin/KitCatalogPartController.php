<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KitCatalogPart;
use App\Models\KitInventory;
use App\Models\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KitCatalogPartController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kit-parts.view')->only('index');
        $this->middleware('permission:kit-parts.create')->only(['store', 'import']);
        $this->middleware('permission:kit-parts.edit')->only('update');
        $this->middleware('permission:kit-parts.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = KitCatalogPart::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($query) use ($search) {
                $query->where('part_number', 'like', '%'.$search.'%')
                    ->orWhere('product_name', 'like', '%'.$search.'%')
                    ->orWhere('model_compatibility', 'like', '%'.$search.'%')
                    ->orWhere('cross_reference', 'like', '%'.$search.'%');
            });
        }

        $parts = $query->paginate(12)->withQueryString();
        $modelNumbers = $parts->getCollection()->pluck('model_compatibility')->filter()->unique()->values();
        $models = Model::query()
            ->whereIn('model_number', $modelNumbers)
            ->orderBy('model_number')
            ->get(['id', 'model_number', 'product_name']);

        return view('admin.kit-parts.index', compact('parts', 'models'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $part = KitCatalogPart::withTrashed()->where('part_number', $data['part_number'])->first();

        if ($part?->trashed()) {
            $part->restore();
            $part->update($data);
        } else {
            $part = KitCatalogPart::create($data);
        }

        $this->syncInventory($part);

        return redirect()->route('admin.kit-parts.index')->with('success', __('Kit part created successfully.'));
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $columns = $this->csvColumns($headers);
        $imported = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
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
                'total_stock' => (int) ($this->csvMoney($this->csvValue($row, $columns, ['total_stock', 'stock'], null)) ?: 0),
                'retail_price' => $this->csvMoney($this->csvValue($row, $columns, ['retail_price', 'retail'], 2)),
                'your_price' => $this->csvMoney($this->csvValue($row, $columns, ['your_price', 'cost'], 3)),
                'cross_reference' => trim((string) $this->csvValue($row, $columns, ['cross_reference_information', 'cross_reference'], 5)) ?: null,
            ], $this->rulesForImport())->validate();

            $part = KitCatalogPart::withTrashed()->where('part_number', $payload['part_number'])->first();
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
                $part = KitCatalogPart::create($payload);
                $imported++;
            }

            $this->syncInventory($part);
        }

        fclose($handle);

        return redirect()
            ->route('admin.kit-parts.index')
            ->with('success', __("Imported {$imported} new kit part(s), updated {$updated} existing kit part(s)."));
    }

    public function update(Request $request, KitCatalogPart $kitPart)
    {
        $oldPartNumber = $kitPart->part_number;
        $data = $this->validatedPayload($request, $kitPart);
        $data['updated_by'] = $request->user()->id;
        $kitPart->update($data);
        if ($oldPartNumber !== $kitPart->part_number) {
            KitInventory::query()->where('part_name', $oldPartNumber)->delete();
        }
        $this->syncInventory($kitPart);

        return redirect()->route('admin.kit-parts.index')->with('success', __('Kit part updated successfully.'));
    }

    public function destroy(KitCatalogPart $kitPart)
    {
        $kitPart->delete();
        KitInventory::query()->where('part_name', $kitPart->part_number)->delete();

        return redirect()->route('admin.kit-parts.index')->with('success', __('Kit part deleted successfully.'));
    }

    private function validatedPayload(Request $request, ?KitCatalogPart $part = null): array
    {
        $data = $request->validate($this->rules($part));
        $data['part_number'] = $this->normalizeIdentifier($data['part_number']);

        return $data;
    }

    private function rules(?KitCatalogPart $part = null): array
    {
        return [
            'part_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kit_catalog_parts', 'part_number')->ignore($part)->whereNull('deleted_at'),
            ],
            'product_name' => ['nullable', 'string', 'max:255'],
            'model_compatibility' => ['nullable', 'string', 'max:255'],
            'total_stock' => ['nullable', 'integer', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'your_price' => ['required', 'numeric', 'min:0'],
            'cross_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function rulesForImport(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'model_compatibility' => ['nullable', 'string', 'max:255'],
            'total_stock' => ['nullable', 'integer', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'your_price' => ['required', 'numeric', 'min:0'],
            'cross_reference' => ['nullable', 'string', 'max:255'],
        ];
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

    private function csvMoney(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $normalized === '' || $normalized === '-' ? 0.0 : (float) $normalized;
    }

    private function syncInventory(KitCatalogPart $part): void
    {
        KitInventory::updateOrCreate(
            ['part_name' => $part->part_number],
            [
                'current_stock' => $part->total_stock,
                'min_level' => 0,
            ]
        );
    }
}
