<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:categories.manage');
    }

    public function index(): View
    {
        $categories = Category::query()
            ->with(['subcategories' => fn ($query) => $query->orderBy('name')])
            ->withCount(['models', 'appliances'])
            ->orderBy('name')
            ->get();

        $this->attachSubcategoryItemCounts($categories);

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create([
            'name' => $request->validated('name'),
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([
            'name' => $request->validated('name'),
            'status' => $request->validated('status'),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function attachSubcategoryItemCounts(Collection $categories): void
    {
        $counts = TruckAppliance::query()
            ->select('category_id', 'subcategory')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereNotNull('subcategory')
            ->where('subcategory', '<>', '')
            ->groupBy('category_id', 'subcategory')
            ->get()
            ->keyBy(fn (TruckAppliance $appliance): string => $appliance->category_id.'|'.$appliance->subcategory);

        foreach ($categories as $category) {
            foreach ($category->subcategories as $subcategory) {
                $subcategory->item_count = (int) ($counts->get($category->id.'|'.$subcategory->name)?->aggregate ?? 0);
            }
        }
    }
}
