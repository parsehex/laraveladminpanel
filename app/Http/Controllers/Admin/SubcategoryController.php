<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;

class SubcategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:categories.manage');
    }

    public function store(StoreSubcategoryRequest $request, Category $category): RedirectResponse
    {
        $category->subcategories()->create([
            'name' => $request->validated('subcategory_name'),
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Subcategory created successfully.'));
    }

    public function update(UpdateSubcategoryRequest $request, Category $category, Subcategory $subcategory): RedirectResponse
    {
        $previousName = $subcategory->name;
        $name = $request->validated('subcategory_name');

        $subcategory->update([
            'name' => $name,
            'status' => $request->validated('status'),
            'updated_by' => $request->user()->id,
        ]);

        if ($previousName !== $name) {
            TruckAppliance::query()
                ->where('category_id', $category->id)
                ->where('subcategory', $previousName)
                ->update(['subcategory' => $name]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Subcategory updated successfully.'));
    }
}
