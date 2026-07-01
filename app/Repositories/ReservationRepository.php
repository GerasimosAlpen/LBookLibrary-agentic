<?php

namespace App\Repositories;

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ReservationRepository
{
    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * All reservations, newest first (admin view).
     */
    public function all(): Collection
    {
        return Reservation::with(['user', 'book'])
            ->orderByDesc('reserved_at')
            ->get();
    }

    /**
     * Reservations belonging to a user, newest first.
     */
    public function forUser(User $user): Collection
    {
        return Reservation::with(['book'])
            ->where('user_id', $user->id)
            ->orderByDesc('reserved_at')
            ->get();
    }

    /**
     * Find a reservation by id, or return null.
     */
    public function findById(int $id): ?Reservation
    {
        return Reservation::with(['user', 'book'])->find($id);
    }

    /**
     * All PENDING reservations for a given book, ordered by queue position.
     */
    public function pendingForBook(Book $book): Collection
    {
        return Reservation::with(['user'])
            ->where('book_id', $book->id)
            ->where('status', ReservationStatus::PENDING)
            ->orderBy('queue_position')
            ->get();
    }

    /**
     * All reservations for a book (all statuses), ordered by queue_position asc.
     */
    public function allForBook(Book $book): Collection
    {
        return Reservation::with(['user'])
            ->where('book_id', $book->id)
            ->orderBy('queue_position')
            ->orderByDesc('reserved_at')
            ->get();
    }

    /**
     * Check whether the user already has an active (PENDING) reservation for this book.
     */
    public function hasPendingReservation(User $user, Book $book): bool
    {
        return Reservation::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', ReservationStatus::PENDING)
            ->exists();
    }

    /**
     * Return the earliest PENDING reservation for a book (lowest queue_position).
     */
    public function earliestPending(Book $book): ?Reservation
    {
        return Reservation::where('book_id', $book->id)
            ->where('status', ReservationStatus::PENDING)
            ->orderBy('queue_position')
            ->first();
    }

    /**
     * Count current PENDING reservations for a book.
     */
    public function countPending(Book $book): int
    {
        return Reservation::where('book_id', $book->id)
            ->where('status', ReservationStatus::PENDING)
            ->count();
    }

    // ─── Mutations ────────────────────────────────────────────────────────────

    /**
     * Create a new PENDING reservation at the end of the queue.
     */
    public function create(User $user, Book $book, int $queuePosition): Reservation
    {
        return Reservation::create([
            'user_id'        => $user->id,
            'book_id'        => $book->id,
            'queue_position' => $queuePosition,
            'status'         => ReservationStatus::PENDING,
            'reserved_at'    => now(),
        ]);
    }

    /**
     * Mark a reservation as CANCELLED.
     */
    public function markCancelled(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => ReservationStatus::CANCELLED]);
        return $reservation->refresh();
    }

    /**
     * Mark a reservation as FULFILLED.
     */
    public function markFulfilled(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => ReservationStatus::FULFILLED]);
        return $reservation->refresh();
    }

    /**
     * Recalculate queue positions for all PENDING reservations of a book.
     * Ordered by current queue_position ascending (preserving FCFS order).
     */
    public function recalculateQueue(Book $book): void
    {
        $pending = Reservation::where('book_id', $book->id)
            ->where('status', ReservationStatus::PENDING)
            ->orderBy('queue_position')
            ->get();

        foreach ($pending as $index => $reservation) {
            $reservation->update(['queue_position' => $index + 1]);
        }
    }
}
