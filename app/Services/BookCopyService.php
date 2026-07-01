<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Repositories\BookCopyRepository;
use App\Repositories\BookRepository;
use Illuminate\Database\Eloquent\Collection;

class BookCopyService
{
    public function __construct(
        private readonly BookCopyRepository $copyRepository,
        private readonly BookRepository     $bookRepository,
    ) {}

    /**
     * Resolve the book or abort 404.
     */
    public function resolveBook(int $bookId): Book
    {
        $book = $this->bookRepository->findById($bookId);

        if ($book === null) {
            abort(404, 'Book not found.');
        }

        return $book;
    }

    /**
     * Retrieve all copies for a book.
     */
    public function listCopies(int $bookId): Collection
    {
        $book = $this->resolveBook($bookId);
        return $this->copyRepository->allForBook($book);
    }

    /**
     * Retrieve a single copy that belongs to a book, or abort 404.
     */
    public function findCopyOrFail(Book $book, int $copyId): BookCopy
    {
        $copy = $this->copyRepository->findForBook($book, $copyId);

        if ($copy === null) {
            abort(404, 'Book copy not found.');
        }

        return $copy;
    }

    /**
     * Create a new physical copy for a book.
     */
    public function createCopy(int $bookId, array $data): BookCopy
    {
        $book = $this->resolveBook($bookId);
        return $this->copyRepository->create($book, $data);
    }

    /**
     * Update an existing copy.
     */
    public function updateCopy(int $bookId, int $copyId, array $data): BookCopy
    {
        $book = $this->resolveBook($bookId);
        $copy = $this->findCopyOrFail($book, $copyId);

        $this->assertValidStatusTransition($copy->status, CopyStatus::from($data['status']));

        return $this->copyRepository->update($copy, $data);
    }

    /**
     * Delete a copy.
     */
    public function deleteCopy(int $bookId, int $copyId): void
    {
        $book = $this->resolveBook($bookId);
        $copy = $this->findCopyOrFail($book, $copyId);
        $this->copyRepository->delete($copy);
    }

    /**
     * Return availability statistics for a book.
     *
     * @return array{total:int,available:int,borrowed:int,reserved:int,lost:int}
     */
    public function availability(int $bookId): array
    {
        $book = $this->resolveBook($bookId);
        return $this->copyRepository->availabilityFor($book);
    }

    /**
     * Validate that a status transition is allowed.
     *
     * Rejected transitions:
     *  - LOST  → AVAILABLE  (a lost copy cannot simply become available again)
     *  - LOST  → BORROWED   (a lost copy cannot be borrowed)
     *  - BORROWED → AVAILABLE is fine (returned)
     *  - RESERVED → BORROWED is fine (checked out)
     *  - Any → LOST is fine (marked lost)
     *  - Any → same status is allowed (no-op update)
     */
    public function assertValidStatusTransition(CopyStatus $from, CopyStatus $to): void
    {
        $forbidden = [
            CopyStatus::LOST->value => [CopyStatus::AVAILABLE, CopyStatus::BORROWED],
        ];

        if (isset($forbidden[$from->value]) && in_array($to, $forbidden[$from->value], true)) {
            abort(422, "Invalid status transition from {$from->value} to {$to->value}.");
        }
    }

    /**
     * Return all valid status values as strings (for views / API).
     *
     * @return string[]
     */
    public function statuses(): array
    {
        return array_column(CopyStatus::cases(), 'value');
    }
}
