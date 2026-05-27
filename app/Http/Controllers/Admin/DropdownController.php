<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Model;
use App\Models\Part;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DropdownController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim();

        $categories = Category::query()
            ->where('status', 1)
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => $categories->getCollection()->map(fn (Category $category) => [
                'id' => $request->input('value_field') === 'name' ? $category->name : $category->id,
                'value' => $category->name,
                'category_id' => $category->id,
                'text' => $category->name,
            ])->values(),
            'next_page' => $categories->hasMorePages() ? $categories->currentPage() + 1 : null,
        ]);
    }

    public function subcategories(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim();
        $category = trim((string) $request->input('category'));

        $subcategories = Subcategory::query()
            ->with('category')
            ->where('status', 1)
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', $category)))
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => $subcategories->getCollection()->map(fn (Subcategory $subcategory) => [
                'id' => $subcategory->name,
                'value' => $subcategory->name,
                'text' => $subcategory->name,
            ])->values(),
            'next_page' => $subcategories->hasMorePages() ? $subcategories->currentPage() + 1 : null,
        ]);
    }

    public function models(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim();
        $valueField = $request->input('value_field') === 'id' ? 'id' : 'model_number';

        $models = Model::query()
            ->where('status', 1)
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('model_number', 'like', '%'.$search.'%')
                        ->orWhere('product_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('model_number')
            ->paginate(20);

        return response()->json([
            'data' => $models->getCollection()->map(fn (Model $model) => [
                'id' => $valueField === 'id' ? $model->id : $model->model_number,
                'text' => $model->model_number.($model->product_name ? ' - '.$model->product_name : ''),
            ])->values(),
            'next_page' => $models->hasMorePages() ? $models->currentPage() + 1 : null,
        ]);
    }

    public function truckModelInfo(Request $request): JsonResponse
    {
        $category = trim((string) $request->input('category'));
        $partial = $request->boolean('partial');
        $modelNumber = strtoupper(trim((string) $request->input('model_number')));

        $query = Model::query()
            ->with('category')
            ->where('status', 1)
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', $category)))
            ->when($modelNumber !== '', function ($query) use ($modelNumber, $partial) {
                $partial
                    ? $query->where('model_number', 'like', $modelNumber.'%')
                    : $query->where('model_number', $modelNumber);
            })
            ->orderBy('model_number')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => $query->isNotEmpty(),
            'suggestions' => $query->map(fn (Model $model) => [
                'model_number' => $model->model_number,
                'brand' => $model->brand,
                'product_name' => $model->product_name,
                'msrp' => $model->msrp,
            ])->values(),
        ]);
    }

    public function brands(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim();

        $brands = Brand::query()
            ->where('status', 1)
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => $brands->getCollection()->map(fn (Brand $brand) => [
                'id' => $brand->name,
                'text' => $brand->name,
            ])->values(),
            'next_page' => $brands->hasMorePages() ? $brands->currentPage() + 1 : null,
        ]);
    }

    public function kitParts(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim();

        $parts = Part::query()
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('part_number', 'like', '%'.$search.'%')
                        ->orWhere('product_name', 'like', '%'.$search.'%')
                        ->orWhere('model_compatibility', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('part_number')
            ->paginate(20);

        return response()->json([
            'data' => $parts->getCollection()->map(fn (Part $part) => [
                'id' => $part->part_number,
                'text' => $part->part_number.($part->product_name ? ' - '.$part->product_name : '').' (stock: '.$part->total_stock.', cost: $'.number_format($this->partCost($part), 2).')',
                'value' => $part->part_number,
                'stock' => $part->total_stock,
                'cost' => $this->partCost($part),
            ])->values(),
            'next_page' => $parts->hasMorePages() ? $parts->currentPage() + 1 : null,
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('category.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('Category created successfully.'),
            'item' => [
                'id' => $category->id,
                'text' => $category->name,
            ],
        ], 201);
    }

    public function storeModel(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('models.create'), 403);

        $data = $request->validate([
            'model_number' => ['required', 'string', 'max:255', Rule::unique('models', 'model_number')],
            'category' => ['nullable', 'exists:categories,name'],
        ]);
        $categoryId = ! empty($data['category'])
            ? Category::query()->where('name', $data['category'])->value('id')
            : null;

        $model = Model::create([
            'model_number' => $data['model_number'],
            'category_id' => $categoryId,
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('Model created successfully.'),
            'item' => [
                'id' => $model->id,
                'value' => $model->model_number,
                'text' => $model->model_number,
            ],
        ], 201);
    }

    public function storeSubcategory(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('category.create'), 403);

        $data = $request->validate([
            'category' => ['required', 'exists:categories,name'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')->where(function ($query) use ($request) {
                    $categoryId = Category::query()->where('name', $request->input('category'))->value('id');

                    return $query->where('category_id', $categoryId);
                }),
            ],
        ]);
        $category = Category::query()->where('name', $data['category'])->firstOrFail();

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => $data['name'],
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('Subcategory created successfully.'),
            'item' => [
                'id' => $subcategory->name,
                'value' => $subcategory->name,
                'text' => $subcategory->name,
            ],
        ], 201);
    }

    public function storeBrand(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can('models.create')
                || $request->user()?->can('models.edit')
                || $request->user()?->can('appliance.create')
                || $request->user()?->can('appliance.edit'),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')],
        ]);

        $brand = Brand::create([
            'name' => $data['name'],
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('Brand created successfully.'),
            'item' => [
                'id' => $brand->name,
                'value' => $brand->name,
                'text' => $brand->name,
            ],
        ], 201);
    }

    public function storeKitPart(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('kits.manage'), 403);

        $data = $request->validate([
            'part_name' => ['required', 'string', 'max:255', Rule::unique('parts', 'part_number')->whereNull('deleted_at')],
            'total_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $part = Part::create([
            'part_number' => trim($data['part_name']),
            'total_stock' => $data['total_stock'] ?? 0,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('Part added successfully.'),
            'item' => [
                'id' => $part->part_number,
                'value' => $part->part_number,
                'text' => $part->part_number.' (stock: '.$part->total_stock.', cost: $'.number_format($this->partCost($part), 2).')',
                'stock' => $part->total_stock,
                'cost' => $this->partCost($part),
            ],
        ], 201);
    }

    private function partCost(Part $part): float
    {
        $yourPrice = (float) $part->your_price;

        return $yourPrice > 0 ? $yourPrice : (float) $part->retail_price;
    }
}
