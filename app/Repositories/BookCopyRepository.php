<?php

namespace App\Repositories;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Collection;

class BookCopyRepository
{
    /**
     * Return all copies for a given book, ordered by id.
     */
    public function allForBook(Book $book): Collection
    {
        return $book->copies()->orderBy('id')->get();
    }

    /**
     * Find a single copy belonging to a specific book, or null.
     */
    public function findForBook(Book $book, int $copyId): ?BookCopy
    {
        return $book->copies()->find($copyId);
    }

    /**
     * Create a new copy for the given book.
     */
    public function create(Book $book, array $data): BookCopy
    {
        return $book->copies()->create([
            'status' => $data['status'] ?? CopyStatus::AVAILABLE,
        ]);
    }

    /**
     * Update a copy's status.
     */
    public function update(BookCopy $copy, array $data): BookCopy
    {
        $copy->update([
            'status' => $data['status'],
        ]);

        return $copy->refresh();
    }

    /**
     * Delete a copy.
     */
    public function delete(BookCopy $copy): bool
    {
        return (bool) $copy->delete();
    }

    /**
     * Return counts grouped by status for availability calculation.
     *
     * @return array{total:int,available:int,borrowed:int,reserved:int,lost:int}
     */
    public function availabilityFor(Book $book): array
    {
        $copies = $book->copies()->get();

        $total    = $copies->count();
        $available = $copies->where('status', CopyStatus::AVAILABLE)->count();
        $borrowed  = $copies->where('status', CopyStatus::BORROWED)->count();
        $reserved  = $copies->where('status', CopyStatus::RESERVED)->count();
        $lost      = $copies->where('status', CopyStatus::LOST)->count();

        return compact('total', 'available', 'borrowed', 'reserved', 'lost');
    }
}
