<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Model;
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
                'id' => $category->id,
                'text' => $category->name,
            ])->values(),
            'next_page' => $categories->hasMorePages() ? $categories->currentPage() + 1 : null,
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

    public function storeCategory(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('category.create') || $request->user()?->can('models.create'), 403);

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
        ]);

        $model = Model::create([
            'model_number' => $data['model_number'],
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
}
