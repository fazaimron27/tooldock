<?php

namespace Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Http\Requests\StoreCategoryRequest;
use Modules\Categories\Http\Requests\UpdateCategoryRequest;
use Modules\Categories\Models\Category;

class CategoriesController extends Controller
{
    /**
     * Display a paginated listing of categories.
     *
     * Supports server-side search, sorting, pagination, and filtering by type.
     */
    public function index(DatatableQueryService $datatableService, Request $request): Response
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::with(['parent']);

        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        $defaultPerPage = (int) settings('categories_per_page', 20);
        $defaultSort = settings('categories_default_sort', 'created_at');
        $defaultDirection = settings('categories_default_sort_direction', 'desc');

        $categories = $datatableService->build(
            $query,
            [
                'searchFields' => ['name', 'slug', 'description'],
                'allowedSorts' => ['name', 'type', 'created_at', 'updated_at'],
                'defaultSort' => $defaultSort,
                'defaultDirection' => $defaultDirection,
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        $types = Category::select('type')
            ->distinct()
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        return Inertia::render('Modules::Categories/Index', [
            'categories' => $categories,
            'defaultPerPage' => $defaultPerPage,
            'types' => $types,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        $this->authorize('create', Category::class);

        $parentCategories = Category::select('id', 'name', 'type')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        $types = Category::select('type')
            ->distinct()
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        $defaultTypes = explode(',', settings('categories_default_types', 'product,finance,project'));
        $defaultTypes = array_map('trim', $defaultTypes);
        $defaultTypes = array_filter($defaultTypes);
        $types = array_unique(array_merge($defaultTypes, $types));
        sort($types);

        return Inertia::render('Modules::Categories/Create', [
            'parentCategories' => $parentCategories,
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): Response
    {
        $this->authorize('update', $category);

        $parentCategories = Category::select('id', 'name', 'type')
            ->where('id', '!=', $category->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        $types = Category::select('type')
            ->distinct()
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        $defaultTypes = explode(',', settings('categories_default_types', 'product,finance,project'));
        $defaultTypes = array_map('trim', $defaultTypes);
        $defaultTypes = array_filter($defaultTypes);
        $types = array_unique(array_merge($defaultTypes, $types));
        sort($types);

        return Inertia::render('Modules::Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
            'types' => $types,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->children()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Cannot delete category with child categories.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
