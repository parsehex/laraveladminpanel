<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModelRequest;
use App\Http\Requests\UpdateModelRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Model;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:models.view')->only('index');
        $this->middleware('permission:models.create')->only('store');
        $this->middleware('permission:models.edit')->only('update');
        $this->middleware('permission:models.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Model::class);

        $query = Model::query()->with('category')->latest();

        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($query) use ($search) {
                $query->where('model_number', 'like', '%'.$search.'%')
                    ->orWhere('product_name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%');
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $models = $query->paginate($perPage)->withQueryString();
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

        return view('admin.models.index', compact('models', 'categories', 'perPage'));
    }

    public function store(StoreModelRequest $request)
    {
        $this->authorize('create', Model::class);

        $data = $request->validated();
        $data['msrp'] = $data['msrp'] ?? 0;
        $data['status'] = 1;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        Model::create($data);

        return redirect()->route('admin.models.index')->with('success', __('Model created successfully.'));
    }

    public function update(UpdateModelRequest $request, Model $model)
    {
        $this->authorize('update', $model);

        $data = $request->validated();
        $data['msrp'] = $data['msrp'] ?? 0;
        $data['updated_by'] = $request->user()->id;

        $this->syncBrand($data['brand'] ?? null, $request->user()->id);

        $model->update($data);

        return redirect()->route('admin.models.index')->with('success', __('Model updated successfully.'));
    }

    public function destroy(Request $request, Model $model)
    {
        $this->authorize('delete', $model);

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
}
