<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Category::withCount('books')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::with('books.author')->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create(['name' => $data['name']]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update(['name' => $data['name']]);
        return $category;
    }

    public function delete(Category $category): bool
    {
        $category->books()->detach();
        return (bool) $category->delete();
    }

    public function booksPaginate(Category $category, int $perPage = 12): LengthAwarePaginator
    {
        return $category->books()
            ->with(['author', 'categories'])
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();
    }
}
