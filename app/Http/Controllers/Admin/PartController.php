<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Models\Model;
use App\Models\Part;
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

        $query = Part::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            if($request->filled('is_from_model_section')){
                $query->where('model_compatibility', 'like', '%'.$search.'%');
            }else{
                $query->where(function ($query) use ($search) {
                    $query->where('part_number', 'like', '%'.$search.'%')
                        ->orWhere('product_name', 'like', '%'.$search.'%')
                        ->orWhere('model_compatibility', 'like', '%'.$search.'%')
                        ->orWhere('cross_reference', 'like', '%'.$search.'%');
                });
            }
        }

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

        return view('admin.parts.index', compact('parts', 'models'));
    }

    public function store(StorePartRequest $request)
    {
        $this->authorize('create', Part::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        Part::create($data);

        return redirect()->route('admin.parts.index')->with('success', __('Part created successfully.'));
    }

    public function import(Request $request)
    {
        $this->authorize('create', Part::class);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        fgetcsv($handle);
        $imported = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 7) {
                continue;
            }

            $partNumber = $this->normalizeIdentifier((string) ($row[1] ?? ''));

            if ($partNumber === '') {
                continue;
            }

            $payload = validator([
                'part_number' => $partNumber,
                'product_name' => null,
                'model_compatibility' => trim((string) ($row[6] ?? '')) ?: null,
                'total_stock' => 0,
                'retail_price' => $row[2] ?? null,
                'your_price' => $row[3] ?? null,
                'cross_reference' => trim((string) ($row[5] ?? '')) ?: null,
            ], [
                'part_number' => ['required', 'string', 'max:255'],
                'product_name' => ['nullable', 'string', 'max:255'],
                'model_compatibility' => ['nullable', 'string', 'max:255'],
                'total_stock' => ['nullable', 'integer', 'min:0'],
                'retail_price' => ['required', 'numeric', 'min:0'],
                'your_price' => ['required', 'numeric', 'min:0'],
                'cross_reference' => ['nullable', 'string', 'max:255'],
            ])->validate();

            $part = Part::query()->where('part_number', $payload['part_number'])->first();
            $payload['total_stock'] = $payload['total_stock'] ?? 0;
            $payload['updated_by'] = $request->user()->id;

            if ($part) {
                $part->update($payload);
                $updated++;
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
}
