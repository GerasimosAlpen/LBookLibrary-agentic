<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\TransactionStatus;
use App\Models\BookCopy;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\BookCopyRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /** Fine rate per overdue day in the currency unit (e.g. 1000 IDR / day) */
    private const FINE_PER_DAY = 1000;

    /** Default loan duration in days */
    private const LOAN_DAYS = 14;

    /** Default extension duration in days */
    private const EXTENSION_DAYS = 7;

    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly BookCopyRepository    $copyRepository,
    ) {}

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * Return all transactions (admin / librarian view).
     */
    public function listAll(): Collection
    {
        return $this->transactionRepository->all();
    }

    /**
     * Return transactions belonging to the authenticated user.
     */
    public function listForUser(User $user): Collection
    {
        return $this->transactionRepository->forUser($user);
    }

    /**
     * Find a transaction by id, or abort 404.
     */
    public function findOrFail(int $id): Transaction
    {
        $transaction = $this->transactionRepository->findById($id);

        if ($transaction === null) {
            abort(404, 'Transaction not found.');
        }

        return $transaction;
    }

    /**
     * Return all overdue transactions.
     * Also synchronizes ACTIVE → OVERDUE for past-due records.
     */
    public function listOverdue(): Collection
    {
        $this->synchronizeOverdueStatuses();

        return $this->transactionRepository->allOverdue();
    }

    // ─── Borrowing ────────────────────────────────────────────────────────────

    /**
     * Borrow a book copy for the given user.
     *
     * Rules enforced:
     *  - Copy must exist.
     *  - Copy status must be AVAILABLE.
     *  - Copy must not already have an active / overdue transaction.
     *
     * Atomically:
     *  - Creates a Transaction (ACTIVE).
     *  - Sets BookCopy status to BORROWED.
     */
    public function borrow(User $user, int $copyId): Transaction
    {
        return DB::transaction(function () use ($user, $copyId) {
            $copy = BookCopy::find($copyId);

            if ($copy === null) {
                abort(404, 'Book copy not found.');
            }

            if ($copy->status !== CopyStatus::AVAILABLE) {
                abort(422, 'This book copy is not available for borrowing.');
            }

            if ($this->transactionRepository->hasActiveTransaction($copy)) {
                abort(422, 'This book copy already has an active transaction.');
            }

            $now    = now();
            $dueDate = $now->copy()->addDays(self::LOAN_DAYS);

            $transaction = $this->transactionRepository->createBorrow($user, $copy, [
                'borrow_date' => $now->toDateTimeString(),
                'due_date'    => $dueDate->toDateTimeString(),
            ]);

            $copy->update(['status' => CopyStatus::BORROWED]);

            return $transaction->load(['user', 'copy.book']);
        });
    }

    // ─── Returning ────────────────────────────────────────────────────────────

    /**
     * Return a borrowed book copy.
     *
     * Rules enforced:
     *  - Transaction must be ACTIVE or OVERDUE.
     *  - Returned transactions cannot be returned again.
     *
     * Atomically:
     *  - Marks Transaction as RETURNED.
     *  - Stores return_date.
     *  - Calculates and stores fine.
     *  - Sets BookCopy status back to AVAILABLE.
     */
    public function returnCopy(Transaction $transaction): Transaction
    {
        if ($transaction->status === TransactionStatus::RETURNED) {
            abort(422, 'This transaction has already been returned.');
        }

        return DB::transaction(function () use ($transaction) {
            $now  = now();
            $fine = $this->calculateFine($transaction, $now);

            $transaction = $this->transactionRepository->markReturned(
                $transaction,
                $now->toDateTimeString()
            );

            if ($fine > 0) {
                $this->transactionRepository->updateFine($transaction, $fine);
            }

            $transaction->copy->update(['status' => CopyStatus::AVAILABLE]);

            return $transaction->refresh()->load(['user', 'copy.book']);
        });
    }

    // ─── Extension ────────────────────────────────────────────────────────────

    /**
     * Extend the due date of a transaction.
     *
     * Rules enforced:
     *  - Transaction must be ACTIVE (not OVERDUE, not RETURNED).
     *  - RETURNED transactions cannot be extended.
     *  - OVERDUE transactions cannot be extended via this endpoint.
     */
    public function extend(Transaction $transaction, ?int $days = null): Transaction
    {
        if ($transaction->status === TransactionStatus::RETURNED) {
            abort(422, 'A returned transaction cannot be extended.');
        }

        if ($transaction->status === TransactionStatus::OVERDUE) {
            abort(422, 'An overdue transaction cannot be extended. Please return the book first.');
        }

        $extensionDays = $days ?? self::EXTENSION_DAYS;
        $newDueDate    = $transaction->due_date->copy()->addDays($extensionDays);

        return $this->transactionRepository->extendDueDate(
            $transaction,
            $newDueDate->toDateTimeString()
        );
    }

    // ─── Fine Calculation ─────────────────────────────────────────────────────

    /**
     * Calculate the fine for a transaction at the time of return.
     * Fine = number of overdue days × FINE_PER_DAY.
     * Returns 0 if no overdue.
     */
    public function calculateFine(Transaction $transaction, ?\DateTimeInterface $returnAt = null): float
    {
        $returnAt ??= now();

        if ($transaction->due_date === null) {
            return 0;
        }

        $dueDate    = $transaction->due_date;
        $overdueSec = $dueDate->diffInSeconds($returnAt, false);

        // Not overdue (returned on time or before due date)
        if ($overdueSec <= 0) {
            return 0;
        }

        $overdueDays = (int) ceil($overdueSec / 86400);

        return max(0, $overdueDays * self::FINE_PER_DAY);
    }

    // ─── Overdue Synchronization ──────────────────────────────────────────────

    /**
     * Synchronize statuses: transition ACTIVE → OVERDUE for past-due records.
     * Called before any overdue listing.
     */
    public function synchronizeOverdueStatuses(): void
    {
        $pastDue = $this->transactionRepository->activePastDue();

        foreach ($pastDue as $transaction) {
            $this->transactionRepository->updateStatus($transaction, TransactionStatus::OVERDUE);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return the fine rate per day (for display purposes).
     */
    public function finePerDay(): int
    {
        return self::FINE_PER_DAY;
    }

    /**
     * Return the default loan duration in days.
     */
    public function loanDays(): int
    {
        return self::LOAN_DAYS;
    }
}
