<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function findOrFail(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);

        if ($category === null) {
            abort(404, 'Category not found.');
        }

        return $category;
    }

    public function create(array $data): Category
    {
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data): Category
    {
        $category = $this->findOrFail($id);
        return $this->categoryRepository->update($category, $data);
    }

    public function delete(int $id): void
    {
        $category = $this->findOrFail($id);
        $this->categoryRepository->delete($category);
    }

    public function booksPaginate(int $id, int $perPage = 12): LengthAwarePaginator
    {
        $category = $this->findOrFail($id);
        return $this->categoryRepository->booksPaginate($category, $perPage);
    }
}
