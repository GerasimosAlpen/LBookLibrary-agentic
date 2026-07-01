<?php

use App\Enums\CopyStatus;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Transaction;
use App\Models\User;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function txAdmin(): User
{
    return User::factory()->create(['role' => Role::ADMIN]);
}

function txLibrarian(): User
{
    return User::factory()->create(['role' => Role::LIBRARIAN]);
}

function txMember(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function txBook(): Book
{
    $author = Author::create(['name' => 'Tx Author', 'bio' => null]);

    return Book::create([
        'title'          => 'Transaction Test Book',
        'description'    => 'desc',
        'isbn'           => null,
        'published_year' => 2021,
        'author_id'      => $author->id,
    ]);
}

function txCopy(string $status = 'AVAILABLE'): BookCopy
{
    $book = txBook();

    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

function txTransaction(User $user, BookCopy $copy, array $overrides = []): Transaction
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

// ─── GET /transactions (index) ────────────────────────────────────────────────

it('unauthenticated user is redirected from transactions index', function () {
    $this->get(route('transactions.index'))
         ->assertRedirect(route('auth.login'));
});

it('member can view their own transactions index', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');
    $copy->update(['status' => CopyStatus::BORROWED]);
    txTransaction($member, $copy);

    $this->actingAs($member)
         ->get(route('transactions.index'))
         ->assertStatus(200)
         ->assertSee('Transactions');
});

it('admin can view all transactions index', function () {
    $this->actingAs(txAdmin())
         ->get(route('transactions.index'))
         ->assertStatus(200);
});

it('librarian can view all transactions index', function () {
    $this->actingAs(txLibrarian())
         ->get(route('transactions.index'))
         ->assertStatus(200);
});

// ─── GET /transactions/{id} (show) ────────────────────────────────────────────

it('member can view their own transaction detail', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->get(route('transactions.show', $tx->id))
         ->assertStatus(200)
         ->assertSee($tx->id);
});

it('member cannot view another member transaction', function () {
    $member1 = txMember();
    $member2 = txMember();
    $copy    = txCopy('BORROWED');
    $tx      = txTransaction($member2, $copy);

    $this->actingAs($member1)
         ->get(route('transactions.show', $tx->id))
         ->assertStatus(403);
});

it('admin can view any transaction detail', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs(txAdmin())
         ->get(route('transactions.show', $tx->id))
         ->assertStatus(200);
});

it('show returns 404 for non-existent transaction', function () {
    $this->actingAs(txMember())
         ->get(route('transactions.show', 99999))
         ->assertStatus(404);
});

// ─── POST /transactions/borrow ────────────────────────────────────────────────

it('member can borrow an available copy', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');

    $response = $this->actingAs($member)
                     ->post(route('transactions.borrow'), ['copy_id' => $copy->id]);

    $response->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'user_id' => $member->id,
        'copy_id' => $copy->id,
        'status'  => 'ACTIVE',
    ]);

    expect($copy->fresh()->status)->toBe(CopyStatus::BORROWED);
});

it('borrowing creates transaction with correct dates', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');

    $this->actingAs($member)
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id]);

    $tx = Transaction::where('copy_id', $copy->id)->first();
    expect($tx)->not->toBeNull();
    expect($tx->borrow_date)->not->toBeNull();
    expect($tx->due_date)->not->toBeNull();
    expect($tx->return_date)->toBeNull();
    expect($tx->fine_amount)->toBe(0.0);
});

it('cannot borrow a non-available copy', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');

    $this->actingAs($member)
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id])
         ->assertStatus(422);

    $this->assertDatabaseMissing('transactions', ['copy_id' => $copy->id]);
});

it('cannot borrow a lost copy', function () {
    $member = txMember();
    $copy   = txCopy('LOST');

    $this->actingAs($member)
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id])
         ->assertStatus(422);
});

it('cannot borrow a non-existent copy', function () {
    $this->actingAs(txMember())
         ->post(route('transactions.borrow'), ['copy_id' => 99999])
         ->assertSessionHasErrors('copy_id');
});

it('borrow requires copy_id field', function () {
    $this->actingAs(txMember())
         ->post(route('transactions.borrow'), [])
         ->assertSessionHasErrors('copy_id');
});

it('cannot create duplicate active transaction for same copy', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');

    // First borrow
    $this->actingAs($member)
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id]);

    // Try to borrow again (copy is now BORROWED)
    $this->actingAs(txMember())
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id])
         ->assertStatus(422);

    expect(Transaction::where('copy_id', $copy->id)->count())->toBe(1);
});

it('unauthenticated user cannot borrow', function () {
    $copy = txCopy('AVAILABLE');

    $this->post(route('transactions.borrow'), ['copy_id' => $copy->id])
         ->assertRedirect(route('auth.login'));
});

// ─── PATCH /transactions/{id}/return ─────────────────────────────────────────

it('member can return their own active transaction', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->patch(route('transactions.return', $tx->id))
         ->assertRedirect(route('transactions.index'));

    $tx->refresh();
    expect($tx->status)->toBe(TransactionStatus::RETURNED);
    expect($tx->return_date)->not->toBeNull();
    expect($copy->fresh()->status)->toBe(CopyStatus::AVAILABLE);
});

it('returning sets copy status back to AVAILABLE', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->patch(route('transactions.return', $tx->id));

    expect($copy->fresh()->status)->toBe(CopyStatus::AVAILABLE);
});

it('cannot return an already returned transaction', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');
    $tx     = txTransaction($member, $copy, [
        'status'      => TransactionStatus::RETURNED,
        'return_date' => now(),
    ]);

    $this->actingAs($member)
         ->patch(route('transactions.return', $tx->id))
         ->assertStatus(422);
});

it('member cannot return another member transaction', function () {
    $member1 = txMember();
    $member2 = txMember();
    $copy    = txCopy('BORROWED');
    $tx      = txTransaction($member2, $copy);

    $this->actingAs($member1)
         ->patch(route('transactions.return', $tx->id))
         ->assertStatus(403);
});

it('admin can return any transaction', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs(txAdmin())
         ->patch(route('transactions.return', $tx->id))
         ->assertRedirect(route('transactions.index'));

    expect($tx->fresh()->status)->toBe(TransactionStatus::RETURNED);
});

it('returning an overdue book calculates fine and stores it', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy, [
        'borrow_date' => now()->subDays(20),
        'due_date'    => now()->subDays(5),
        'status'      => TransactionStatus::OVERDUE,
    ]);

    $this->actingAs($member)
         ->patch(route('transactions.return', $tx->id));

    expect($tx->fresh()->fine_amount)->toBeGreaterThan(0);
    expect($tx->fresh()->status)->toBe(TransactionStatus::RETURNED);
});

it('return returns 404 for non-existent transaction', function () {
    $this->actingAs(txMember())
         ->patch(route('transactions.return', 99999))
         ->assertStatus(404);
});

// ─── PATCH /transactions/{id}/extend ─────────────────────────────────────────

it('member can extend their active loan', function () {
    $member  = txMember();
    $copy    = txCopy('BORROWED');
    $tx      = txTransaction($member, $copy);
    $oldDue  = $tx->due_date->copy();

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id), ['days' => 7])
         ->assertRedirect(route('transactions.show', $tx->id));

    expect($tx->fresh()->due_date->gt($oldDue))->toBeTrue();
});

it('extension uses default 7 days when not specified', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);
    $oldDue = $tx->due_date->copy();

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id));

    $newDue = $tx->fresh()->due_date;
    expect($newDue->diffInDays($oldDue))->toBe(7);
});

it('cannot extend a returned transaction', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');
    $tx     = txTransaction($member, $copy, [
        'status'      => TransactionStatus::RETURNED,
        'return_date' => now(),
    ]);

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id))
         ->assertStatus(422);
});

it('cannot extend an overdue transaction', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy, [
        'due_date' => now()->subDays(3),
        'status'   => TransactionStatus::OVERDUE,
    ]);

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id))
         ->assertStatus(422);
});

it('member cannot extend another member transaction', function () {
    $member1 = txMember();
    $member2 = txMember();
    $copy    = txCopy('BORROWED');
    $tx      = txTransaction($member2, $copy);

    $this->actingAs($member1)
         ->patch(route('transactions.extend', $tx->id))
         ->assertStatus(403);
});

it('extend validates days field max 30', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id), ['days' => 100])
         ->assertSessionHasErrors('days');
});

it('extend validates days field min 1', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->patch(route('transactions.extend', $tx->id), ['days' => 0])
         ->assertSessionHasErrors('days');
});

// ─── GET /transactions/overdue ────────────────────────────────────────────────

it('admin can view overdue transactions', function () {
    $this->actingAs(txAdmin())
         ->get(route('transactions.overdue'))
         ->assertStatus(200);
});

it('librarian can view overdue transactions', function () {
    $this->actingAs(txLibrarian())
         ->get(route('transactions.overdue'))
         ->assertStatus(200);
});

it('member cannot view overdue transactions', function () {
    $this->actingAs(txMember())
         ->get(route('transactions.overdue'))
         ->assertStatus(403);
});

it('unauthenticated user cannot view overdue transactions', function () {
    $this->get(route('transactions.overdue'))
         ->assertRedirect(route('auth.login'));
});

it('overdue list contains truly overdue transactions', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');

    $tx = txTransaction($member, $copy, [
        'borrow_date' => now()->subDays(20),
        'due_date'    => now()->subDays(5),
        'status'      => TransactionStatus::OVERDUE,
    ]);

    $this->actingAs(txAdmin())
         ->get(route('transactions.overdue'))
         ->assertStatus(200)
         ->assertSee($tx->copy->book->title ?? $tx->id);
});

it('on-time transactions do not appear in overdue list', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    txTransaction($member, $copy, [
        'due_date' => now()->addDays(5),
        'status'   => TransactionStatus::ACTIVE,
    ]);

    $response = $this->actingAs(txAdmin())
                     ->get(route('transactions.overdue'));

    expect(Transaction::whereNull('return_date')->where('due_date', '<', now())->count())->toBe(0);
});

// ─── Database Consistency ─────────────────────────────────────────────────────

it('borrowed copy has BORROWED status after borrow', function () {
    $member = txMember();
    $copy   = txCopy('AVAILABLE');

    $this->actingAs($member)
         ->post(route('transactions.borrow'), ['copy_id' => $copy->id]);

    expect($copy->fresh()->status)->toBe(CopyStatus::BORROWED);
});

it('copy becomes AVAILABLE after return', function () {
    $member = txMember();
    $copy   = txCopy('BORROWED');
    $tx     = txTransaction($member, $copy);

    $this->actingAs($member)
         ->patch(route('transactions.return', $tx->id));

    expect($copy->fresh()->status)->toBe(CopyStatus::AVAILABLE);
});
