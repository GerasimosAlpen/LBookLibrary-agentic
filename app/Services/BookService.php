<?php

namespace App\Services;

use App\Models\Book;
use App\Repositories\BookRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {}

    public function list(
        ?string $search = null,
        ?int $categoryId = null,
        string $sortBy = 'title',
        string $sortDir = 'asc',
        int $perPage = 12
    ): LengthAwarePaginator {
        return $this->bookRepository->paginate($perPage, $search, $categoryId, $sortBy, $sortDir);
    }

    public function findOrFail(int $id): Book
    {
        $book = $this->bookRepository->findById($id);

        if ($book === null) {
            abort(404, 'Book not found.');
        }

        return $book;
    }

    public function create(array $data): Book
    {
        return $this->bookRepository->create($data);
    }

    public function update(int $id, array $data): Book
    {
        $book = $this->findOrFail($id);
        return $this->bookRepository->update($book, $data);
    }

    public function delete(int $id): void
    {
        $book = $this->findOrFail($id);
        $this->bookRepository->delete($book);
    }
}
