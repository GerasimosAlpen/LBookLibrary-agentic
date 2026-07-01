<?php

namespace App\Repositories;

use App\Enums\CopyStatus;
use App\Enums\TransactionStatus;
use App\Models\BookCopy;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    /**
     * Return all transactions, ordered by newest first, with eager loaded relations.
     */
    public function all(): Collection
    {
        return Transaction::with(['user', 'copy.book'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Return paginated transactions for a given user.
     */
    public function forUser(User $user): Collection
    {
        return Transaction::with(['copy.book'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Find a transaction by id, or return null.
     */
    public function findById(int $id): ?Transaction
    {
        return Transaction::with(['user', 'copy.book'])->find($id);
    }

    /**
     * Check whether a copy has an active (non-returned) transaction.
     */
    public function hasActiveTransaction(BookCopy $copy): bool
    {
        return Transaction::where('copy_id', $copy->id)
            ->whereIn('status', [TransactionStatus::ACTIVE, TransactionStatus::OVERDUE])
            ->exists();
    }

    /**
     * Create a new borrow transaction.
     */
    public function createBorrow(User $user, BookCopy $copy, array $data): Transaction
    {
        return Transaction::create([
            'user_id'     => $user->id,
            'copy_id'     => $copy->id,
            'borrow_date' => $data['borrow_date'],
            'due_date'    => $data['due_date'],
            'return_date' => null,
            'fine_amount' => 0,
            'status'      => TransactionStatus::ACTIVE,
        ]);
    }

    /**
     * Mark a transaction as returned and store the return date.
     */
    public function markReturned(Transaction $transaction, string $returnDate): Transaction
    {
        $transaction->update([
            'status'      => TransactionStatus::RETURNED,
            'return_date' => $returnDate,
        ]);

        return $transaction->refresh();
    }

    /**
     * Extend the due date of an active transaction.
     */
    public function extendDueDate(Transaction $transaction, string $newDueDate): Transaction
    {
        $transaction->update([
            'due_date' => $newDueDate,
        ]);

        return $transaction->refresh();
    }

    /**
     * Update the fine amount on a transaction.
     */
    public function updateFine(Transaction $transaction, float $amount): Transaction
    {
        $transaction->update([
            'fine_amount' => max(0, $amount),
        ]);

        return $transaction->refresh();
    }

    /**
     * Update a transaction's status.
     */
    public function updateStatus(Transaction $transaction, TransactionStatus $status): Transaction
    {
        $transaction->update(['status' => $status]);

        return $transaction->refresh();
    }

    /**
     * Return all overdue transactions:
     * due_date < now AND return_date IS NULL.
     */
    public function allOverdue(): Collection
    {
        return Transaction::with(['user', 'copy.book'])
            ->whereNull('return_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Return all transactions that are currently past due but still
     * carry ACTIVE status (need synchronization to OVERDUE).
     */
    public function activePastDue(): Collection
    {
        return Transaction::where('status', TransactionStatus::ACTIVE)
            ->whereNull('return_date')
            ->where('due_date', '<', now())
            ->get();
    }
}
