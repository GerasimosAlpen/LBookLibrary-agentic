<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Repositories\BookRepository;
use App\Repositories\ReservationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly BookRepository        $bookRepository,
    ) {}

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * All reservations (admin/librarian view).
     */
    public function listAll(): Collection
    {
        return $this->reservationRepository->all();
    }

    /**
     * Reservations for the authenticated user.
     */
    public function listForUser(User $user): Collection
    {
        return $this->reservationRepository->forUser($user);
    }

    /**
     * Find a reservation by id or abort 404.
     */
    public function findOrFail(int $id): Reservation
    {
        $reservation = $this->reservationRepository->findById($id);

        if ($reservation === null) {
            abort(404, 'Reservation not found.');
        }

        return $reservation;
    }

    /**
     * Find a book by id or abort 404.
     */
    public function findBookOrFail(int $bookId): Book
    {
        $book = $this->bookRepository->findById($bookId);

        if ($book === null) {
            abort(404, 'Book not found.');
        }

        return $book;
    }

    /**
     * All reservations (all statuses) for a book.
     */
    public function listForBook(Book $book): Collection
    {
        return $this->reservationRepository->allForBook($book);
    }

    /**
     * PENDING reservations for a book, ordered by queue position.
     */
    public function pendingQueueForBook(Book $book): Collection
    {
        return $this->reservationRepository->pendingForBook($book);
    }

    // ─── Reservation Creation ─────────────────────────────────────────────────

    /**
     * Create a reservation for the given user and book.
     *
     * Business rules enforced:
     *  - Book must exist.
     *  - At least one AVAILABLE copy must NOT exist (cannot reserve when copies available).
     *  - User must not already have a PENDING reservation for this book.
     *
     * Atomically:
     *  - Assigns next queue position.
     *  - Creates Reservation (PENDING).
     */
    public function reserve(User $user, int $bookId): Reservation
    {
        return DB::transaction(function () use ($user, $bookId) {
            $book = $this->findBookOrFail($bookId);

            // Rule: Cannot reserve if AVAILABLE copies exist
            $availableCount = $book->copies()
                ->where('status', CopyStatus::AVAILABLE)
                ->count();

            if ($availableCount > 0) {
                abort(422, 'This book has available copies. Please borrow it directly instead of reserving.');
            }

            // Rule: No duplicate active reservation
            if ($this->reservationRepository->hasPendingReservation($user, $book)) {
                abort(422, 'You already have an active reservation for this book.');
            }

            // Assign next queue position
            $nextPosition = $this->reservationRepository->countPending($book) + 1;

            $reservation = $this->reservationRepository->create($user, $book, $nextPosition);

            return $reservation->load(['user', 'book']);
        });
    }

    // ─── Cancellation ─────────────────────────────────────────────────────────

    /**
     * Cancel a PENDING reservation.
     *
     * Business rules enforced:
     *  - Must be PENDING (not already CANCELLED or FULFILLED).
     *
     * Atomically:
     *  - Marks reservation CANCELLED.
     *  - Recalculates queue positions for the book.
     */
    public function cancel(Reservation $reservation): Reservation
    {
        if ($reservation->status === ReservationStatus::FULFILLED) {
            abort(422, 'A fulfilled reservation cannot be cancelled.');
        }

        if ($reservation->status === ReservationStatus::CANCELLED) {
            abort(422, 'This reservation has already been cancelled.');
        }

        return DB::transaction(function () use ($reservation) {
            $book = $reservation->book;

            $this->reservationRepository->markCancelled($reservation);
            $this->reservationRepository->recalculateQueue($book);

            return $reservation->refresh()->load(['user', 'book']);
        });
    }

    // ─── Fulfillment ──────────────────────────────────────────────────────────

    /**
     * Fulfill the earliest PENDING reservation for a book (when a copy becomes available).
     *
     * Called when a copy's status transitions to AVAILABLE.
     * If no PENDING reservation exists, does nothing.
     *
     * Atomically:
     *  - Marks the earliest reservation FULFILLED.
     *  - Recalculates queue positions.
     */
    public function fulfillEarliest(Book $book): ?Reservation
    {
        return DB::transaction(function () use ($book) {
            $earliest = $this->reservationRepository->earliestPending($book);

            if ($earliest === null) {
                return null;
            }

            $this->reservationRepository->markFulfilled($earliest);
            $this->reservationRepository->recalculateQueue($book);

            return $earliest->refresh()->load(['user', 'book']);
        });
    }

    // ─── Eligibility Helpers ──────────────────────────────────────────────────

    /**
     * Check if a user is eligible to reserve a book.
     * Returns an array: ['eligible' => bool, 'reason' => string|null]
     */
    public function eligibility(User $user, Book $book): array
    {
        $availableCount = $book->copies()
            ->where('status', CopyStatus::AVAILABLE)
            ->count();

        if ($availableCount > 0) {
            return [
                'eligible' => false,
                'reason'   => 'This book has ' . $availableCount . ' available cop' . ($availableCount === 1 ? 'y' : 'ies') . '. Please borrow it directly.',
            ];
        }

        if ($this->reservationRepository->hasPendingReservation($user, $book)) {
            $existing = Reservation::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', ReservationStatus::PENDING)
                ->first();

            return [
                'eligible'         => false,
                'reason'           => 'You already have an active reservation for this book.',
                'existing_queue'   => $existing?->queue_position,
            ];
        }

        $nextPosition = $this->reservationRepository->countPending($book) + 1;

        return [
            'eligible'      => true,
            'reason'        => null,
            'queue_position' => $nextPosition,
        ];
    }
}
