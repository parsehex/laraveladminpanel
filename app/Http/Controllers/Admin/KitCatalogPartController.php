<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\KitCatalogPartCsvImport;
use App\Imports\StagedCsvImport;
use App\Models\KitCatalogPart;
use App\Models\KitInventory;
use App\Models\Model;
use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KitCatalogPartController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kit-parts.view')->only('index');
        $this->middleware('permission:kit-parts.create')->only([
            'store',
            'importPreview',
            'importReview',
            'importConfirm',
            'importCancel',
        ]);
        $this->middleware('permission:kit-parts.edit')->only('update');
        $this->middleware('permission:kit-parts.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = KitCatalogPart::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($query) use ($search) {
                $query->whereLike('part_number', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereLike('model_compatibility', '%'.$search.'%')
                    ->orWhereLike('cross_reference', '%'.$search.'%');
            });
        }

        $parts = PageSize::paginate($query, $request);
        $modelNumbers = $parts->getCollection()
            ->flatMap(fn (KitCatalogPart $part) => $part->compatibilityModelNumbers())
            ->unique()
            ->values();
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

    public function importPreview(Request $request)
    {
        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $this->stagedCsvImport()->stage($request, $data['csv_file']);

        return redirect()->route('admin.kit-parts.import.review');
    }

    public function importReview(Request $request, KitCatalogPartCsvImport $importer)
    {
        try {
            $staged = $this->stagedCsvImport()->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.kit-parts.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $preview = $importer->preview($this->stagedCsvImport()->absolutePath($staged));

        return view('admin.shared.csv-import-review', [
            'title' => 'Review Kit Parts Import',
            'preview' => $preview,
            'confirmRoute' => route('admin.kit-parts.import.confirm'),
            'cancelRoute' => route('admin.kit-parts.import.cancel'),
            'backRoute' => route('admin.kit-parts.index'),
            'backLabel' => 'Back to kit parts',
            'matchHelp' => 'Review the changes below. Matching uses part number (including soft-deleted parts, which will be restored). Confirm only when there are no errors.',
        ]);
    }

    public function importConfirm(Request $request, KitCatalogPartCsvImport $importer)
    {
        $staging = $this->stagedCsvImport();

        try {
            $staged = $staging->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.kit-parts.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $absolutePath = $staging->absolutePath($staged);

        try {
            $preview = $importer->preview($absolutePath);

            if (! $preview->canConfirm()) {
                return redirect()
                    ->route('admin.kit-parts.import.review')
                    ->with('error', __('Fix the CSV errors before confirming the import.'));
            }

            $result = $importer->commit($absolutePath, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.kit-parts.import.review')
                ->withErrors($exception->errors());
        }

        $staging->discard($request);

        return redirect()
            ->route('admin.kit-parts.index')
            ->with('success', __("Imported {$result->imported} new kit part(s), updated {$result->updated} existing kit part(s)."));
    }

    public function importCancel(Request $request)
    {
        $this->stagedCsvImport()->discard($request);

        return redirect()->route('admin.kit-parts.index')->with('success', __('Import cancelled.'));
    }

    private function stagedCsvImport(): StagedCsvImport
    {
        return new StagedCsvImport('kit_part_csv_import');
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
        $data = $request->validate([
            ...$this->rules($part),
            'model_ids' => ['nullable', 'array'],
            'model_ids.*' => ['integer', 'exists:models,id'],
            'model_ids_present' => ['nullable', 'boolean'],
        ]);

        $data['part_number'] = $this->normalizeIdentifier($data['part_number']);

        if ($request->boolean('model_ids_present')) {
            $data['model_compatibility'] = $this->modelCompatibilityFromIds($request->input('model_ids', []));
        }

        unset($data['model_ids'], $data['model_ids_present']);

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
            'total_stock' => ['nullable', 'integer', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'your_price' => ['required', 'numeric', 'min:0'],
            'cross_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<int|string>  $modelIds
     */
    private function modelCompatibilityFromIds(array $modelIds): ?string
    {
        $ids = collect($modelIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $label = Model::query()
            ->whereIn('id', $ids)
            ->orderBy('model_number')
            ->pluck('model_number')
            ->implode(', ');

        return $label !== '' ? $label : null;
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
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
