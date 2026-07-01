<?php

use App\Enums\CopyStatus;
use App\Enums\TransactionStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\Role;
use App\Repositories\BookCopyRepository;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeTransactionService(): TransactionService
{
    return new TransactionService(
        new TransactionRepository(),
        new BookCopyRepository(),
    );
}

function txSvcMember(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function txSvcBook(): Book
{
    $author = Author::create(['name' => 'TxSvc Author', 'bio' => null]);

    return Book::create([
        'title'          => 'TxSvc Test Book',
        'description'    => 'desc',
        'isbn'           => null,
        'published_year' => 2022,
        'author_id'      => $author->id,
    ]);
}

function txSvcCopy(string $status = 'AVAILABLE'): BookCopy
{
    $book = txSvcBook();

    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

function txSvcTransaction(User $user, BookCopy $copy, array $overrides = []): Transaction
{
    return Transaction::create(array_merge([
        'user_id'     => $user->id,
        'copy_id'     => $copy->id,
        'borrow_date' => now(),
        'due_date'    => now()->addDays(14),
        'return_date' => null,
        'fine_amount' => 0,
        'status'      => TransactionStatus::ACTIVE,
    ], $overrides));
}

// ─── Fine Calculation ─────────────────────────────────────────────────────────

it('calculates zero fine when returned on time', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->addDays(5),
    ]);

    $fine = $service->calculateFine($tx, now());

    expect($fine)->toBe(0.0);
});

it('calculates zero fine when returned exactly on due date', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');

    $due = now()->addDays(1);
    $tx  = txSvcTransaction($member, $copy, ['due_date' => $due]);

    $fine = $service->calculateFine($tx, $due);

    expect($fine)->toBe(0.0);
});

it('calculates positive fine for overdue return', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->subDays(3),
    ]);

    $fine = $service->calculateFine($tx, now());

    expect($fine)->toBeGreaterThan(0);
});

it('fine is never negative', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->addDays(10),
    ]);

    $fine = $service->calculateFine($tx, now()->subDays(5));

    expect($fine)->toBe(0.0);
});

it('fine increases with more overdue days', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();

    $copy1 = txSvcCopy('BORROWED');
    $tx1   = txSvcTransaction($member, $copy1, ['due_date' => now()->subDays(2)]);
    $fine1 = $service->calculateFine($tx1, now());

    $copy2 = txSvcCopy('BORROWED');
    $tx2   = txSvcTransaction($member, $copy2, ['due_date' => now()->subDays(5)]);
    $fine2 = $service->calculateFine($tx2, now());

    expect($fine2)->toBeGreaterThan($fine1);
});

// ─── Borrowing Business Rules ─────────────────────────────────────────────────

it('borrow creates transaction with ACTIVE status', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');

    $tx = $service->borrow($member, $copy->id);

    expect($tx->status)->toBe(TransactionStatus::ACTIVE);
});

it('borrow sets copy to BORROWED status', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');

    $service->borrow($member, $copy->id);

    expect($copy->fresh()->status)->toBe(CopyStatus::BORROWED);
});

it('borrow sets borrow_date and due_date', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');

    $tx = $service->borrow($member, $copy->id);

    expect($tx->borrow_date)->not->toBeNull();
    expect($tx->due_date)->not->toBeNull();
    expect($tx->return_date)->toBeNull();
});

it('borrow due date is 14 days after borrow date', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');

    $tx = $service->borrow($member, $copy->id);

    expect($tx->due_date->diffInDays($tx->borrow_date))->toBe(14);
});

it('borrow throws 422 for non-available copy', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');

    expect(fn () => $service->borrow($member, $copy->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('borrow throws 422 for lost copy', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('LOST');

    expect(fn () => $service->borrow($member, $copy->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('borrow throws 404 for non-existent copy', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();

    expect(fn () => $service->borrow($member, 99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ─── Return Business Rules ────────────────────────────────────────────────────

it('return sets transaction status to RETURNED', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy);

    $result = $service->returnCopy($tx);

    expect($result->status)->toBe(TransactionStatus::RETURNED);
});

it('return sets return_date on transaction', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy);

    $result = $service->returnCopy($tx);

    expect($result->return_date)->not->toBeNull();
});

it('return sets copy status back to AVAILABLE', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy);

    $service->returnCopy($tx);

    expect($copy->fresh()->status)->toBe(CopyStatus::AVAILABLE);
});

it('cannot return an already returned transaction', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');
    $tx      = txSvcTransaction($member, $copy, [
        'status'      => TransactionStatus::RETURNED,
        'return_date' => now(),
    ]);

    expect(fn () => $service->returnCopy($tx))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('overdue return calculates and stores fine', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->subDays(5),
        'status'   => TransactionStatus::OVERDUE,
    ]);

    $result = $service->returnCopy($tx);

    expect($result->fine_amount)->toBeGreaterThan(0);
});

// ─── Extension Business Rules ─────────────────────────────────────────────────

it('extend increases due date by 7 days by default', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy);
    $oldDue  = $tx->due_date->copy();

    $result = $service->extend($tx);

    expect($result->due_date->diffInDays($oldDue))->toBe(7);
});

it('extend uses custom days when provided', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy);
    $oldDue  = $tx->due_date->copy();

    $result = $service->extend($tx, 14);

    expect($result->due_date->diffInDays($oldDue))->toBe(14);
});

it('cannot extend a returned transaction', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');
    $tx      = txSvcTransaction($member, $copy, [
        'status'      => TransactionStatus::RETURNED,
        'return_date' => now(),
    ]);

    expect(fn () => $service->extend($tx))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('cannot extend an overdue transaction', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->subDays(2),
        'status'   => TransactionStatus::OVERDUE,
    ]);

    expect(fn () => $service->extend($tx))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ─── Overdue Synchronization ──────────────────────────────────────────────────

it('synchronize overdue statuses changes ACTIVE past-due to OVERDUE', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->subDays(3),
        'status'   => TransactionStatus::ACTIVE,
    ]);

    $service->synchronizeOverdueStatuses();

    expect($tx->fresh()->status)->toBe(TransactionStatus::OVERDUE);
});

it('synchronize does not change returned transactions', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('AVAILABLE');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date'    => now()->subDays(3),
        'return_date' => now()->subDays(1),
        'status'      => TransactionStatus::RETURNED,
    ]);

    $service->synchronizeOverdueStatuses();

    expect($tx->fresh()->status)->toBe(TransactionStatus::RETURNED);
});

it('on-time ACTIVE transactions are not changed by synchronize', function () {
    $service = makeTransactionService();
    $member  = txSvcMember();
    $copy    = txSvcCopy('BORROWED');
    $tx      = txSvcTransaction($member, $copy, [
        'due_date' => now()->addDays(5),
        'status'   => TransactionStatus::ACTIVE,
    ]);

    $service->synchronizeOverdueStatuses();

    expect($tx->fresh()->status)->toBe(TransactionStatus::ACTIVE);
});

// ─── Transaction Repository ───────────────────────────────────────────────────

it('repository hasActiveTransaction returns true for active borrowing', function () {
    $repo   = new TransactionRepository();
    $member = txSvcMember();
    $copy   = txSvcCopy('BORROWED');
    txSvcTransaction($member, $copy);

    expect($repo->hasActiveTransaction($copy))->toBeTrue();
});

it('repository hasActiveTransaction returns false for returned borrowing', function () {
    $repo   = new TransactionRepository();
    $member = txSvcMember();
    $copy   = txSvcCopy('AVAILABLE');
    txSvcTransaction($member, $copy, [
        'status'      => TransactionStatus::RETURNED,
        'return_date' => now(),
    ]);

    expect($repo->hasActiveTransaction($copy))->toBeFalse();
});

it('repository allOverdue returns only overdue records', function () {
    $repo   = new TransactionRepository();
    $member = txSvcMember();

    $copy1 = txSvcCopy('BORROWED');
    txSvcTransaction($member, $copy1, [
        'due_date' => now()->subDays(5),
        'status'   => TransactionStatus::OVERDUE,
    ]);

    $copy2 = txSvcCopy('BORROWED');
    txSvcTransaction($member, $copy2, [
        'due_date' => now()->addDays(5),
        'status'   => TransactionStatus::ACTIVE,
    ]);

    $overdue = $repo->allOverdue();
    expect($overdue->count())->toBe(1);
});

it('service loanDays returns 14', function () {
    $service = makeTransactionService();
    expect($service->loanDays())->toBe(14);
});

it('service finePerDay returns positive value', function () {
    $service = makeTransactionService();
    expect($service->finePerDay())->toBeGreaterThan(0);
});
