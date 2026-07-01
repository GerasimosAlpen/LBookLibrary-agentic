<?php

use App\Enums\CopyStatus;
use App\Enums\Role;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;
use App\Models\Author;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function inventoryAdmin(): User
{
    return User::factory()->create(['role' => Role::ADMIN]);
}

function inventoryLibrarian(): User
{
    return User::factory()->create(['role' => Role::LIBRARIAN]);
}

function inventoryMember(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function inventoryBook(array $attrs = []): Book
{
    $author = Author::create(['name' => 'Test Author', 'bio' => null]);
    return Book::create(array_merge([
        'title'       => 'Inventory Test Book',
        'description' => 'desc',
        'isbn'        => null,
        'published_year' => 2020,
        'author_id'   => $author->id,
    ], $attrs));
}

function inventoryCopy(Book $book, string $status = 'AVAILABLE'): BookCopy
{
    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

// ─── GET /books/{id}/copies ───────────────────────────────────────────────────

it('member can view book copies index', function () {
    $book = inventoryBook();
    inventoryCopy($book, 'AVAILABLE');

    $this->actingAs(inventoryMember())
         ->get(route('books.copies.index', $book->id))
         ->assertStatus(200)
         ->assertSee('COPY-');
});

it('admin can view book copies index', function () {
    $book = inventoryBook();

    $this->actingAs(inventoryAdmin())
         ->get(route('books.copies.index', $book->id))
         ->assertStatus(200);
});

it('unauthenticated user is redirected from copies index', function () {
    $book = inventoryBook();

    $this->get(route('books.copies.index', $book->id))
         ->assertRedirect(route('auth.login'));
});

it('returns 404 for copies index on non-existent book', function () {
    $this->actingAs(inventoryMember())
         ->get(route('books.copies.index', 99999))
         ->assertStatus(404);
});

it('copies index shows availability statistics', function () {
    $book = inventoryBook();
    inventoryCopy($book, 'AVAILABLE');
    inventoryCopy($book, 'BORROWED');
    inventoryCopy($book, 'LOST');

    $this->actingAs(inventoryMember())
         ->get(route('books.copies.index', $book->id))
         ->assertStatus(200)
         ->assertSee('3')   // total
         ->assertSee('1');  // available
});

// ─── POST /books/{id}/copies ──────────────────────────────────────────────────

it('admin can create a book copy with default status', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->post(route('books.copies.store', $book->id), [])
         ->assertRedirect(route('books.copies.index', $book->id));

    expect($book->copies()->count())->toBe(1);
    expect($book->copies()->first()->status)->toBe(CopyStatus::AVAILABLE);
});

it('admin can create a book copy with explicit status', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->post(route('books.copies.store', $book->id), ['status' => 'BORROWED'])
         ->assertRedirect();

    expect($book->copies()->first()->status)->toBe(CopyStatus::BORROWED);
});

it('librarian can create a book copy', function () {
    $book      = inventoryBook();
    $librarian = inventoryLibrarian();

    $this->actingAs($librarian)
         ->post(route('books.copies.store', $book->id), ['status' => 'AVAILABLE'])
         ->assertRedirect();

    $this->assertDatabaseHas('book_copies', ['book_id' => $book->id, 'status' => 'AVAILABLE']);
});

it('member cannot create a book copy', function () {
    $book   = inventoryBook();
    $member = inventoryMember();

    $this->actingAs($member)
         ->post(route('books.copies.store', $book->id), ['status' => 'AVAILABLE'])
         ->assertStatus(403);
});

it('copy creation rejects invalid status', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->post(route('books.copies.store', $book->id), ['status' => 'FLYING'])
         ->assertSessionHasErrors('status');
});

it('copy creation returns 404 for non-existent book', function () {
    $this->actingAs(inventoryAdmin())
         ->post(route('books.copies.store', 99999), ['status' => 'AVAILABLE'])
         ->assertStatus(404);
});

// ─── PUT /books/{id}/copies/{copyId} ─────────────────────────────────────────

it('admin can update a copy status', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'BORROWED'])
         ->assertRedirect(route('books.copies.index', $book->id));

    expect($copy->fresh()->status)->toBe(CopyStatus::BORROWED);
});

it('librarian can update a copy status', function () {
    $book      = inventoryBook();
    $copy      = inventoryCopy($book, 'AVAILABLE');
    $librarian = inventoryLibrarian();

    $this->actingAs($librarian)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'RESERVED'])
         ->assertRedirect();

    expect($copy->fresh()->status)->toBe(CopyStatus::RESERVED);
});

it('member cannot update a copy status', function () {
    $book   = inventoryBook();
    $copy   = inventoryCopy($book, 'AVAILABLE');
    $member = inventoryMember();

    $this->actingAs($member)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'BORROWED'])
         ->assertStatus(403);
});

it('copy update requires status field', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, $copy->id]), [])
         ->assertSessionHasErrors('status');
});

it('copy update rejects invalid status value', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'INVALID'])
         ->assertSessionHasErrors('status');
});

it('copy update returns 404 for non-existent copy', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, 99999]), ['status' => 'AVAILABLE'])
         ->assertStatus(404);
});

it('copy update returns 404 for non-existent book', function () {
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [99999, 1]), ['status' => 'AVAILABLE'])
         ->assertStatus(404);
});

it('copy update rejects LOST to AVAILABLE transition', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'LOST');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'AVAILABLE'])
         ->assertStatus(422);
});

it('copy update rejects LOST to BORROWED transition', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'LOST');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->put(route('books.copies.update', [$book->id, $copy->id]), ['status' => 'BORROWED'])
         ->assertStatus(422);
});

// ─── DELETE /books/{id}/copies/{copyId} ──────────────────────────────────────

it('admin can delete a book copy', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->delete(route('books.copies.destroy', [$book->id, $copy->id]))
         ->assertRedirect(route('books.copies.index', $book->id));

    $this->assertDatabaseMissing('book_copies', ['id' => $copy->id]);
});

it('librarian can delete a book copy', function () {
    $book      = inventoryBook();
    $copy      = inventoryCopy($book, 'RESERVED');
    $librarian = inventoryLibrarian();

    $this->actingAs($librarian)
         ->delete(route('books.copies.destroy', [$book->id, $copy->id]))
         ->assertRedirect();

    $this->assertDatabaseMissing('book_copies', ['id' => $copy->id]);
});

it('member cannot delete a book copy', function () {
    $book   = inventoryBook();
    $copy   = inventoryCopy($book, 'AVAILABLE');
    $member = inventoryMember();

    $this->actingAs($member)
         ->delete(route('books.copies.destroy', [$book->id, $copy->id]))
         ->assertStatus(403);

    $this->assertDatabaseHas('book_copies', ['id' => $copy->id]);
});

it('copy delete returns 404 for non-existent copy', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    $this->actingAs($admin)
         ->delete(route('books.copies.destroy', [$book->id, 99999]))
         ->assertStatus(404);
});

it('deleting a copy decrements inventory count', function () {
    $book  = inventoryBook();
    $copy1 = inventoryCopy($book, 'AVAILABLE');
    $copy2 = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    expect($book->copies()->count())->toBe(2);

    $this->actingAs($admin)
         ->delete(route('books.copies.destroy', [$book->id, $copy1->id]));

    expect($book->copies()->count())->toBe(1);
});

// ─── GET /books/{id}/availability ────────────────────────────────────────────

it('member can retrieve book availability as JSON', function () {
    $book = inventoryBook();
    inventoryCopy($book, 'AVAILABLE');
    inventoryCopy($book, 'AVAILABLE');
    inventoryCopy($book, 'BORROWED');
    inventoryCopy($book, 'LOST');

    $response = $this->actingAs(inventoryMember())
                     ->getJson(route('books.availability', $book->id));

    $response->assertStatus(200)
             ->assertJson([
                 'total'     => 4,
                 'available' => 2,
                 'borrowed'  => 1,
                 'reserved'  => 0,
                 'lost'      => 1,
             ]);
});

it('admin can retrieve book availability', function () {
    $book = inventoryBook();

    $response = $this->actingAs(inventoryAdmin())
                     ->getJson(route('books.availability', $book->id));

    $response->assertStatus(200)
             ->assertJsonStructure(['total', 'available', 'borrowed', 'reserved', 'lost']);
});

it('availability returns 404 for non-existent book', function () {
    $this->actingAs(inventoryMember())
         ->getJson(route('books.availability', 99999))
         ->assertStatus(404);
});

it('availability updates immediately after creating a copy', function () {
    $book  = inventoryBook();
    $admin = inventoryAdmin();

    // Start: zero copies
    $before = $this->actingAs($admin)
                   ->getJson(route('books.availability', $book->id))
                   ->json();

    expect($before['total'])->toBe(0);

    // Create a copy
    $this->actingAs($admin)
         ->post(route('books.copies.store', $book->id), ['status' => 'AVAILABLE']);

    $after = $this->actingAs($admin)
                  ->getJson(route('books.availability', $book->id))
                  ->json();

    expect($after['total'])->toBe(1);
    expect($after['available'])->toBe(1);
});

it('availability updates immediately after deleting a copy', function () {
    $book  = inventoryBook();
    $copy  = inventoryCopy($book, 'AVAILABLE');
    $admin = inventoryAdmin();

    $before = $this->actingAs($admin)
                   ->getJson(route('books.availability', $book->id))
                   ->json();

    expect($before['total'])->toBe(1);

    $this->actingAs($admin)
         ->delete(route('books.copies.destroy', [$book->id, $copy->id]));

    $after = $this->actingAs($admin)
                  ->getJson(route('books.availability', $book->id))
                  ->json();

    expect($after['total'])->toBe(0);
});

// ─── Book Show: Inventory Panel ───────────────────────────────────────────────

it('book show page displays inventory stats', function () {
    $book = inventoryBook();
    inventoryCopy($book, 'AVAILABLE');
    inventoryCopy($book, 'BORROWED');

    $this->actingAs(inventoryMember())
         ->get(route('books.show', $book->id))
         ->assertStatus(200)
         ->assertSee('Inventory');
});
