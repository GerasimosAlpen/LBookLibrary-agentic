<?php

use App\Enums\CopyStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function rAdmin(): User
{
    return User::factory()->create(['role' => Role::ADMIN]);
}

function rLibrarian(): User
{
    return User::factory()->create(['role' => Role::LIBRARIAN]);
}

function rMember(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function rBook(): Book
{
    $author = Author::create(['name' => 'Res Feature Author', 'bio' => null]);

    return Book::create([
        'title'          => 'Reservation Feature Book',
        'description'    => 'desc',
        'isbn'           => null,
        'published_year' => 2023,
        'author_id'      => $author->id,
    ]);
}

function rCopy(Book $book, string $status = 'AVAILABLE'): BookCopy
{
    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

function rReservation(User $user, Book $book, array $overrides = []): Reservation
{
    $position = Reservation::where('book_id', $book->id)
        ->where('status', ReservationStatus::PENDING)
        ->count() + 1;

    return Reservation::create(array_merge([
        'user_id'        => $user->id,
        'book_id'        => $book->id,
        'queue_position' => $position,
        'status'         => ReservationStatus::PENDING,
        'reserved_at'    => now(),
    ], $overrides));
}

// ─── GET /reservations (index) ────────────────────────────────────────────────

it('unauthenticated user is redirected from reservations index', function () {
    $this->get(route('reservations.index'))
         ->assertRedirect(route('auth.login'));
});

it('member can view their own reservations index', function () {
    $this->actingAs(rMember())
         ->get(route('reservations.index'))
         ->assertStatus(200)
         ->assertSee('Reservations');
});

it('admin can view all reservations index', function () {
    $this->actingAs(rAdmin())
         ->get(route('reservations.index'))
         ->assertStatus(200);
});

it('librarian can view all reservations index', function () {
    $this->actingAs(rLibrarian())
         ->get(route('reservations.index'))
         ->assertStatus(200);
});

// ─── POST /reservations (store) ───────────────────────────────────────────────

it('member can reserve a book with no available copies', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'BORROWED'); // no available copies

    $response = $this->actingAs($member)
                     ->post(route('reservations.store'), ['book_id' => $book->id]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reservations', [
        'user_id' => $member->id,
        'book_id' => $book->id,
        'status'  => 'PENDING',
    ]);
});

it('reservation is created with correct queue position', function () {
    $book    = rBook();
    rCopy($book, 'BORROWED');

    $member1 = rMember();
    $member2 = rMember();

    $this->actingAs($member1)->post(route('reservations.store'), ['book_id' => $book->id]);
    $this->actingAs($member2)->post(route('reservations.store'), ['book_id' => $book->id]);

    $r1 = Reservation::where('user_id', $member1->id)->where('book_id', $book->id)->first();
    $r2 = Reservation::where('user_id', $member2->id)->where('book_id', $book->id)->first();

    expect($r1->queue_position)->toBe(1);
    expect($r2->queue_position)->toBe(2);
});

it('cannot reserve when available copies exist', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'AVAILABLE'); // copy is available

    $this->actingAs($member)
         ->post(route('reservations.store'), ['book_id' => $book->id])
         ->assertStatus(422);

    $this->assertDatabaseMissing('reservations', [
        'user_id' => $member->id,
        'book_id' => $book->id,
    ]);
});

it('cannot create duplicate active reservation for same book', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'BORROWED');

    $this->actingAs($member)->post(route('reservations.store'), ['book_id' => $book->id]);
    $this->actingAs($member)->post(route('reservations.store'), ['book_id' => $book->id])
         ->assertStatus(422);

    expect(Reservation::where('user_id', $member->id)->where('book_id', $book->id)->count())->toBe(1);
});

it('cannot reserve a non-existent book', function () {
    $this->actingAs(rMember())
         ->post(route('reservations.store'), ['book_id' => 99999])
         ->assertSessionHasErrors('book_id');
});

it('reserve requires book_id field', function () {
    $this->actingAs(rMember())
         ->post(route('reservations.store'), [])
         ->assertSessionHasErrors('book_id');
});

it('unauthenticated user cannot reserve', function () {
    $book = rBook();
    $this->post(route('reservations.store'), ['book_id' => $book->id])
         ->assertRedirect(route('auth.login'));
});

it('reservation starts as PENDING status', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'BORROWED');

    $this->actingAs($member)->post(route('reservations.store'), ['book_id' => $book->id]);

    $reservation = Reservation::where('user_id', $member->id)->first();
    expect($reservation->status)->toBe(ReservationStatus::PENDING);
});

it('reservation stores reserved_at timestamp', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'BORROWED');

    $this->actingAs($member)->post(route('reservations.store'), ['book_id' => $book->id]);

    $reservation = Reservation::where('user_id', $member->id)->first();
    expect($reservation->reserved_at)->not->toBeNull();
});

// ─── GET /reservations/{id} (show) ────────────────────────────────────────────

it('member can view their own reservation', function () {
    $member = rMember();
    $book   = rBook();
    $res    = rReservation($member, $book);

    $this->actingAs($member)
         ->get(route('reservations.show', $res->id))
         ->assertStatus(200)
         ->assertSee((string) $res->id);
});

it('member cannot view another members reservation', function () {
    $member1 = rMember();
    $member2 = rMember();
    $book    = rBook();
    $res     = rReservation($member2, $book);

    $this->actingAs($member1)
         ->get(route('reservations.show', $res->id))
         ->assertStatus(403);
});

it('admin can view any reservation', function () {
    $member = rMember();
    $book   = rBook();
    $res    = rReservation($member, $book);

    $this->actingAs(rAdmin())
         ->get(route('reservations.show', $res->id))
         ->assertStatus(200);
});

it('show returns 404 for non-existent reservation', function () {
    $this->actingAs(rMember())
         ->get(route('reservations.show', 99999))
         ->assertStatus(404);
});

// ─── PATCH /reservations/{id}/cancel ──────────────────────────────────────────

it('member can cancel their own pending reservation', function () {
    $member = rMember();
    $book   = rBook();
    $res    = rReservation($member, $book);

    $this->actingAs($member)
         ->patch(route('reservations.cancel', $res->id))
         ->assertRedirect(route('reservations.index'));

    expect($res->fresh()->status)->toBe(ReservationStatus::CANCELLED);
});

it('cancellation recalculates queue positions', function () {
    $book   = rBook();
    $m1     = rMember();
    $m2     = rMember();
    $m3     = rMember();

    $r1 = rReservation($m1, $book);
    $r2 = rReservation($m2, $book);
    $r3 = rReservation($m3, $book);

    // Cancel the first
    $this->actingAs($m1)->patch(route('reservations.cancel', $r1->id));

    // r2 should now be position 1, r3 should be position 2
    expect($r2->fresh()->queue_position)->toBe(1);
    expect($r3->fresh()->queue_position)->toBe(2);
});

it('member cannot cancel another members reservation', function () {
    $m1  = rMember();
    $m2  = rMember();
    $res = rReservation($m2, rBook());

    $this->actingAs($m1)
         ->patch(route('reservations.cancel', $res->id))
         ->assertStatus(403);
});

it('cannot cancel an already cancelled reservation', function () {
    $member = rMember();
    $res    = rReservation($member, rBook(), ['status' => ReservationStatus::CANCELLED]);

    $this->actingAs($member)
         ->patch(route('reservations.cancel', $res->id))
         ->assertStatus(422);
});

it('cannot cancel a fulfilled reservation', function () {
    $member = rMember();
    $res    = rReservation($member, rBook(), [
        'status'         => ReservationStatus::FULFILLED,
        'queue_position' => 0,
    ]);

    $this->actingAs($member)
         ->patch(route('reservations.cancel', $res->id))
         ->assertStatus(422);
});

it('admin can cancel any reservation', function () {
    $member = rMember();
    $res    = rReservation($member, rBook());

    $this->actingAs(rAdmin())
         ->patch(route('reservations.cancel', $res->id))
         ->assertRedirect(route('reservations.index'));

    expect($res->fresh()->status)->toBe(ReservationStatus::CANCELLED);
});

it('cancel returns 404 for non-existent reservation', function () {
    $this->actingAs(rMember())
         ->patch(route('reservations.cancel', 99999))
         ->assertStatus(404);
});

// ─── GET /books/{id}/reservations ─────────────────────────────────────────────

it('admin can view book reservations queue', function () {
    $book = rBook();
    rReservation(rMember(), $book);

    $this->actingAs(rAdmin())
         ->get(route('books.reservations', $book->id))
         ->assertStatus(200);
});

it('librarian can view book reservations queue', function () {
    $book = rBook();

    $this->actingAs(rLibrarian())
         ->get(route('books.reservations', $book->id))
         ->assertStatus(200);
});

it('member cannot view book reservations queue', function () {
    $book = rBook();

    $this->actingAs(rMember())
         ->get(route('books.reservations', $book->id))
         ->assertStatus(403);
});

it('unauthenticated user cannot view book reservations', function () {
    $book = rBook();
    $this->get(route('books.reservations', $book->id))
         ->assertRedirect(route('auth.login'));
});

it('book reservations returns 404 for non-existent book', function () {
    $this->actingAs(rAdmin())
         ->get(route('books.reservations', 99999))
         ->assertStatus(404);
});

// ─── Queue ordering ───────────────────────────────────────────────────────────

it('reservations maintain FCFS queue order', function () {
    $book = rBook();
    $m1   = rMember();
    $m2   = rMember();
    $m3   = rMember();
    rCopy($book, 'BORROWED');

    $this->actingAs($m1)->post(route('reservations.store'), ['book_id' => $book->id]);
    $this->actingAs($m2)->post(route('reservations.store'), ['book_id' => $book->id]);
    $this->actingAs($m3)->post(route('reservations.store'), ['book_id' => $book->id]);

    $positions = Reservation::where('book_id', $book->id)
        ->where('status', ReservationStatus::PENDING)
        ->orderBy('queue_position')
        ->pluck('user_id')
        ->toArray();

    expect($positions)->toBe([$m1->id, $m2->id, $m3->id]);
});

it('cancelled member slot is filled by shifting queue', function () {
    $book = rBook();
    $m1   = rMember();
    $m2   = rMember();
    rCopy($book, 'BORROWED');

    $this->actingAs($m1)->post(route('reservations.store'), ['book_id' => $book->id]);
    $this->actingAs($m2)->post(route('reservations.store'), ['book_id' => $book->id]);

    $r1 = Reservation::where('user_id', $m1->id)->first();
    $r2 = Reservation::where('user_id', $m2->id)->first();

    expect($r1->queue_position)->toBe(1);
    expect($r2->queue_position)->toBe(2);

    $this->actingAs($m1)->patch(route('reservations.cancel', $r1->id));

    expect($r2->fresh()->queue_position)->toBe(1);
});

// ─── Database Consistency ─────────────────────────────────────────────────────

it('historical (cancelled/fulfilled) reservations are preserved', function () {
    $member = rMember();
    $book   = rBook();
    $res    = rReservation($member, $book);

    $this->actingAs($member)->patch(route('reservations.cancel', $res->id));

    $this->assertDatabaseHas('reservations', [
        'id'     => $res->id,
        'status' => 'CANCELLED',
    ]);
});

it('same user can reserve same book after cancellation', function () {
    $member = rMember();
    $book   = rBook();
    rCopy($book, 'BORROWED');

    $this->actingAs($member)->post(route('reservations.store'), ['book_id' => $book->id]);
    $res = Reservation::where('user_id', $member->id)->first();
    $this->actingAs($member)->patch(route('reservations.cancel', $res->id));

    // Should be able to re-reserve
    $this->actingAs($member)
         ->post(route('reservations.store'), ['book_id' => $book->id])
         ->assertRedirect();

    expect(Reservation::where('user_id', $member->id)->where('status', 'PENDING')->count())->toBe(1);
});
