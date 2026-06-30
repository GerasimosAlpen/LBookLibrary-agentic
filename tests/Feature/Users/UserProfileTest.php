<?php

use App\Models\User;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Transaction;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Enums\CopyStatus;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::MEMBER]);
    $this->admin = User::factory()->create(['role' => Role::ADMIN]);
});

it('allows user to view their own profile', function () {
    $this->actingAs($this->user)
         ->get(route('users.show', $this->user->id))
         ->assertStatus(200)
         ->assertSee($this->user->name);
});

it('prevents user from viewing another profile', function () {
    $otherUser = User::factory()->create();
    $this->actingAs($this->user)
         ->get(route('users.show', $otherUser->id))
         ->assertStatus(403);
});

it('allows admin to view any profile', function () {
    $this->actingAs($this->admin)
         ->get(route('users.show', $this->user->id))
         ->assertStatus(200);
});

it('allows user to update their profile', function () {
    $this->actingAs($this->user)
         ->put(route('users.update', $this->user->id), [
             'name' => 'New Name',
             'email' => 'newemail@example.com'
         ])
         ->assertRedirect(route('users.show', $this->user->id));

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'New Name', 'email' => 'newemail@example.com']);
});

it('prevents user from updating to an existing email', function () {
    $otherUser = User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->user)
         ->put(route('users.update', $this->user->id), [
             'name' => 'New Name',
             'email' => 'taken@example.com'
         ])
         ->assertSessionHasErrors('email');
});

it('allows user to view borrowing history', function () {
    $book = Book::factory()->create();
    $copy = BookCopy::create([
        'book_id' => $book->id,
        'barcode' => 'TEST-BC-' . uniqid(),
        'status' => CopyStatus::BORROWED
    ]);
    Transaction::create([
        'user_id' => $this->user->id,
        'copy_id' => $copy->id,
        'borrow_date' => now(),
        'due_date' => now()->addDays(14),
        'status' => TransactionStatus::ACTIVE
    ]);

    $this->actingAs($this->user)
         ->get(route('users.history'))
         ->assertStatus(200)
         ->assertSee($book->title);
});

it('allows user to view recommendations', function () {
    $this->actingAs($this->user)
         ->get(route('users.recommendations'))
         ->assertStatus(200);
});
