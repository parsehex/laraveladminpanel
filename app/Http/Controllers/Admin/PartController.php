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
            $query->where(function ($query) use ($search) {
                $query->where('part_number', 'like', '%'.$search.'%')
                    ->orWhere('product_name', 'like', '%'.$search.'%')
                    ->orWhere('model_compatibility', 'like', '%'.$search.'%')
                    ->orWhere('cross_reference', 'like', '%'.$search.'%');
            });
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
}
