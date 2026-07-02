<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $categories = $this->categoryService->list();
        return view('catalog.categories.index', compact('categories'));
    }

    public function show(int $category): View
    {
        $category = $this->categoryService->findOrFail($category);
        $books    = $this->categoryService->booksPaginate($category->id);

        return view('catalog.categories.show', compact('category', 'books'));
    }

    public function create(): View
    {
        $this->authorizeManage();
        return view('catalog.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categoryService->create($request->validated());

        return redirect()
            ->route('categories.show', $category->id)
            ->with('success', 'Category created successfully.');
    }

    public function edit(int $category): View
    {
        $this->authorizeManage();

        $category = $this->categoryService->findOrFail($category);
        return view('catalog.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, int $category): RedirectResponse
    {
        $updated = $this->categoryService->update($category, $request->validated());

        return redirect()
            ->route('categories.show', $updated->id)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(int $category): RedirectResponse
    {
        $this->authorizeManage();

        $this->categoryService->delete($category);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function authorizeManage(): void
    {
        $role = Auth::user()?->role;
        if ($role !== Role::ADMIN && $role !== Role::LIBRARIAN) {
            abort(403, 'Unauthorized.');
        }
    }
}
