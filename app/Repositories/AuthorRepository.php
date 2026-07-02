<?php

namespace App\Repositories;

use App\Models\Author;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuthorRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Author::withCount('books')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(): Collection
    {
        return Author::orderBy('name')->get();
    }

    public function findById(int $id): ?Author
    {
        return Author::with(['books.categories'])->find($id);
    }

    public function create(array $data): Author
    {
        return Author::create([
            'name' => $data['name'],
            'bio'  => $data['bio'] ?? null,
        ]);
    }

    public function update(Author $author, array $data): Author
    {
        $author->update([
            'name' => $data['name'],
            'bio'  => $data['bio'] ?? null,
        ]);

        return $author;
    }

    public function delete(Author $author): bool
    {
        return (bool) $author->delete();
    }

    public function booksPaginate(Author $author, int $perPage = 12): LengthAwarePaginator
    {
        return $author->books()
            ->with('categories')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();
    }
}
