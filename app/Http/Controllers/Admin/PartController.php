<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Imports\PartCsvImport;
use App\Imports\StagedCsvImport;
use App\Models\Model;
use App\Models\Part;
use App\Support\DataTable;
use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:parts.view')->only('index');
        $this->middleware('permission:parts.create')->only([
            'store',
            'importPreview',
            'importReview',
            'importConfirm',
            'importCancel',
        ]);
        $this->middleware('permission:parts.edit')->only('update');
        $this->middleware('permission:parts.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $dataTable = $this->partsIndexDataTable();
        $query = Part::query()->with(['models' => fn ($query) => $query->orderBy('model_number')]);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();

            if ($request->filled('is_from_model_section')) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('models', fn ($models) => $models->whereLike('model_number', '%'.$search.'%'))
                        ->orWhereLike('model_compatibility', '%'.$search.'%');
                });
            } else {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('part_number', '%'.$search.'%')
                        ->orWhereLike('product_name', '%'.$search.'%')
                        ->orWhereLike('model_compatibility', '%'.$search.'%')
                        ->orWhereLike('cross_reference', '%'.$search.'%')
                        ->orWhereHas('models', function ($models) use ($search) {
                            $models->whereLike('model_number', '%'.$search.'%')
                                ->orWhereLike('product_name', '%'.$search.'%');
                        });
                });
            }
        }

        $dataTable->applySorting($query, $request);

        $parts = PageSize::paginate($query, $request);
        $modelIds = $parts->getCollection()
            ->flatMap(fn (Part $part) => $part->models->pluck('id'))
            ->unique()
            ->values();

        $models = Model::query()
            ->whereIn('id', $modelIds)
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
        $data = $request->safe()->except(['model_ids', 'model_ids_present']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $part = Part::withTrashed()->where('part_number', $data['part_number'])->first();

        if ($part?->trashed()) {
            $part->restore();
            $part->update($data);
        } else {
            $part = Part::create($data);
        }

        if ($request->boolean('model_ids_present')) {
            $part->syncCompatibleModels($request->input('model_ids', []));
        }

        return redirect()->route('admin.parts.index')->with('success', __('Part created successfully.'));
    }

    public function importPreview(Request $request)
    {
        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $this->stagedCsvImport()->stage($request, $data['csv_file']);

        return redirect()->route('admin.parts.import.review');
    }

    public function importReview(Request $request, PartCsvImport $importer)
    {
        try {
            $staged = $this->stagedCsvImport()->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.parts.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $preview = $importer->preview($this->stagedCsvImport()->absolutePath($staged));

        return view('admin.shared.csv-import-review', [
            'title' => 'Review Parts Import',
            'preview' => $preview,
            'confirmRoute' => route('admin.parts.import.confirm'),
            'cancelRoute' => route('admin.parts.import.cancel'),
            'backRoute' => route('admin.parts.index'),
            'backLabel' => 'Back to parts',
            'matchHelp' => 'Review the changes below. Matching uses part number (including soft-deleted parts, which will be restored). Confirm only when there are no errors. Total stock from CSV is forced to 0.',
        ]);
    }

    public function importConfirm(Request $request, PartCsvImport $importer)
    {
        $staging = $this->stagedCsvImport();

        try {
            $staged = $staging->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.parts.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $absolutePath = $staging->absolutePath($staged);

        try {
            $preview = $importer->preview($absolutePath);

            if (! $preview->canConfirm()) {
                return redirect()
                    ->route('admin.parts.import.review')
                    ->with('error', __('Fix the CSV errors before confirming the import.'));
            }

            $result = $importer->commit($absolutePath, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.parts.import.review')
                ->withErrors($exception->errors());
        }

        $staging->discard($request);

        return redirect()
            ->route('admin.parts.index')
            ->with('success', __("Imported {$result->imported} new part(s), updated {$result->updated} existing part(s)."));
    }

    public function importCancel(Request $request)
    {
        $this->stagedCsvImport()->discard($request);

        return redirect()->route('admin.parts.index')->with('success', __('Import cancelled.'));
    }

    private function stagedCsvImport(): StagedCsvImport
    {
        return new StagedCsvImport('part_csv_import');
    }

    public function update(UpdatePartRequest $request, Part $part)
    {
        $data = $request->safe()->except(['model_ids', 'model_ids_present']);
        $data['updated_by'] = $request->user()->id;

        $part->update($data);

        if ($request->boolean('model_ids_present')) {
            $part->syncCompatibleModels($request->input('model_ids', []));
        }

        return redirect()->route('admin.parts.index')->with('success', __('Part updated successfully.'));
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return redirect()->route('admin.parts.index')->with('success', __('Part deleted successfully.'));
    }
}
