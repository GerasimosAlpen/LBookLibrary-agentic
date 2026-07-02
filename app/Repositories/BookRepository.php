<?php

namespace App\Repositories;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BookRepository
{
    public function paginate(
        int $perPage = 12,
        ?string $search = null,
        ?int $categoryId = null,
        string $sortBy = 'title',
        string $sortDir = 'asc'
    ): LengthAwarePaginator {
        $allowedSort = ['title', 'published_year'];
        $sortBy      = in_array($sortBy, $allowedSort, true) ? $sortBy : 'title';
        $sortDir     = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        return Book::with(['author', 'categories'])
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                          ->orWhere('isbn', 'like', "%{$search}%")
                          ->orWhereHas('author', fn (Builder $a) =>
                              $a->where('name', 'like', "%{$search}%")
                          );
                });
            })
            ->when($categoryId, fn (Builder $q) =>
                $q->whereHas('categories', fn (Builder $c) =>
                    $c->where('categories.id', $categoryId)
                )
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?Book
    {
        return Book::with(['author', 'categories'])->find($id);
    }

    public function create(array $data): Book
    {
        $book = Book::create([
            'title'          => $data['title'],
            'description'    => $data['description'] ?? '',
            'isbn'           => $data['isbn'] ?? null,
            'published_year' => $data['published_year'] ?? null,
            'author_id'      => $data['author_id'],
        ]);

        if (!empty($data['category_ids'])) {
            $book->categories()->sync($data['category_ids']);
        }

        return $book->load(['author', 'categories']);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update([
            'title'          => $data['title'],
            'description'    => $data['description'] ?? $book->description,
            'isbn'           => array_key_exists('isbn', $data) ? $data['isbn'] : $book->isbn,
            'published_year' => array_key_exists('published_year', $data) ? $data['published_year'] : $book->published_year,
            'author_id'      => $data['author_id'],
        ]);

        $book->categories()->sync($data['category_ids'] ?? []);

        return $book->load(['author', 'categories']);
    }

    public function delete(Book $book): bool
    {
        $book->categories()->detach();
        return (bool) $book->delete();
    }
}
