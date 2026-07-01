<?php

use App\Enums\CopyStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;
use App\Repositories\BookRepository;
use App\Repositories\ReservationRepository;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeReservationService(): ReservationService
{
    return new ReservationService(
        new ReservationRepository(),
        new BookRepository(),
    );
}

function rSvcMember(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function rSvcBook(): Book
{
    $author = Author::create(['name' => 'ReSvc Author', 'bio' => null]);

    return Book::create([
        'title'          => 'ReSvc Test Book',
        'description'    => 'desc',
        'isbn'           => null,
        'published_year' => 2023,
        'author_id'      => $author->id,
    ]);
}

function rSvcCopy(Book $book, string $status = 'AVAILABLE'): BookCopy
{
    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

function rSvcReservation(User $user, Book $book, array $overrides = []): Reservation
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

// ─── Reservation Eligibility ──────────────────────────────────────────────────

it('eligible when no available copies exist and no existing reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $result = $service->eligibility($member, $book);

    expect($result['eligible'])->toBeTrue();
    expect($result['reason'])->toBeNull();
    expect($result['queue_position'])->toBe(1);
});

it('not eligible when available copies exist', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'AVAILABLE');

    $result = $service->eligibility($member, $book);

    expect($result['eligible'])->toBeFalse();
    expect($result['reason'])->toContain('available');
});

it('not eligible when user already has pending reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');
    rSvcReservation($member, $book);

    $result = $service->eligibility($member, $book);

    expect($result['eligible'])->toBeFalse();
    expect($result['existing_queue'])->toBe(1);
});

it('eligible to reserve book with zero copies', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook(); // No copies at all

    $result = $service->eligibility($member, $book);

    expect($result['eligible'])->toBeTrue();
});

// ─── Reservation Creation ─────────────────────────────────────────────────────

it('reserve creates PENDING reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $reservation = $service->reserve($member, $book->id);

    expect($reservation->status)->toBe(ReservationStatus::PENDING);
    expect($reservation->user_id)->toBe($member->id);
    expect($reservation->book_id)->toBe($book->id);
});

it('reserve assigns queue position 1 for first reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $reservation = $service->reserve($member, $book->id);

    expect($reservation->queue_position)->toBe(1);
});

it('reserve assigns incremental queue positions', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $r1 = $service->reserve(rSvcMember(), $book->id);
    $r2 = $service->reserve(rSvcMember(), $book->id);
    $r3 = $service->reserve(rSvcMember(), $book->id);

    expect($r1->queue_position)->toBe(1);
    expect($r2->queue_position)->toBe(2);
    expect($r3->queue_position)->toBe(3);
});

it('reserve stores reserved_at timestamp', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $reservation = $service->reserve($member, $book->id);

    expect($reservation->reserved_at)->not->toBeNull();
});

it('reserve throws 422 when available copies exist', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'AVAILABLE');

    expect(fn () => $service->reserve($member, $book->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('reserve throws 422 for duplicate active reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $service->reserve($member, $book->id);

    expect(fn () => $service->reserve($member, $book->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('reserve throws 404 for non-existent book', function () {
    $service = makeReservationService();
    $member  = rSvcMember();

    expect(fn () => $service->reserve($member, 99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ─── Cancellation Rules ───────────────────────────────────────────────────────

it('cancel sets status to CANCELLED', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $book    = rSvcBook();
    $res     = rSvcReservation($member, $book);

    $result = $service->cancel($res);

    expect($result->status)->toBe(ReservationStatus::CANCELLED);
});

it('cancel recalculates queue positions', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    $m1      = rSvcMember();
    $m2      = rSvcMember();
    $m3      = rSvcMember();

    $r1 = rSvcReservation($m1, $book);
    $r2 = rSvcReservation($m2, $book);
    $r3 = rSvcReservation($m3, $book);

    $service->cancel($r1);

    expect($r2->fresh()->queue_position)->toBe(1);
    expect($r3->fresh()->queue_position)->toBe(2);
});

it('cannot cancel an already cancelled reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $res     = rSvcReservation($member, rSvcBook(), [
        'status' => ReservationStatus::CANCELLED,
    ]);

    expect(fn () => $service->cancel($res))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('cannot cancel a fulfilled reservation', function () {
    $service = makeReservationService();
    $member  = rSvcMember();
    $res     = rSvcReservation($member, rSvcBook(), [
        'status'         => ReservationStatus::FULFILLED,
        'queue_position' => 0,
    ]);

    expect(fn () => $service->cancel($res))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('cancelling middle reservation shifts remaining correctly', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    $m1      = rSvcMember();
    $m2      = rSvcMember();
    $m3      = rSvcMember();

    $r1 = rSvcReservation($m1, $book); // pos 1
    $r2 = rSvcReservation($m2, $book); // pos 2
    $r3 = rSvcReservation($m3, $book); // pos 3

    $service->cancel($r2);

    // r1 stays at 1, r3 moves to 2
    expect($r1->fresh()->queue_position)->toBe(1);
    expect($r3->fresh()->queue_position)->toBe(2);
});

// ─── Fulfillment Rules ────────────────────────────────────────────────────────

it('fulfillEarliest marks the first PENDING reservation as FULFILLED', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    $m1      = rSvcMember();
    $m2      = rSvcMember();

    $r1 = rSvcReservation($m1, $book); // pos 1
    $r2 = rSvcReservation($m2, $book); // pos 2

    $fulfilled = $service->fulfillEarliest($book);

    expect($fulfilled->id)->toBe($r1->id);
    expect($fulfilled->status)->toBe(ReservationStatus::FULFILLED);
});

it('fulfillEarliest returns null when no pending reservations', function () {
    $service = makeReservationService();
    $book    = rSvcBook();

    $result = $service->fulfillEarliest($book);

    expect($result)->toBeNull();
});

it('fulfillEarliest recalculates queue after fulfillment', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    $m1      = rSvcMember();
    $m2      = rSvcMember();
    $m3      = rSvcMember();

    $r1 = rSvcReservation($m1, $book); // pos 1
    $r2 = rSvcReservation($m2, $book); // pos 2
    $r3 = rSvcReservation($m3, $book); // pos 3

    $service->fulfillEarliest($book);

    // r2 moves to 1, r3 moves to 2
    expect($r2->fresh()->queue_position)->toBe(1);
    expect($r3->fresh()->queue_position)->toBe(2);
});

it('fulfilled reservation leaves active queue', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    $member  = rSvcMember();

    $r1 = rSvcReservation($member, $book);

    $service->fulfillEarliest($book);

    $repo = new ReservationRepository();
    expect($repo->countPending($book))->toBe(0);
});

// ─── Queue Position Management ────────────────────────────────────────────────

it('queue position is always sequential starting at 1', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $m1 = rSvcMember();
    $m2 = rSvcMember();
    $m3 = rSvcMember();

    $r1 = $service->reserve($m1, $book->id);
    $r2 = $service->reserve($m2, $book->id);
    $r3 = $service->reserve($m3, $book->id);

    // Cancel middle reservation
    $service->cancel($r2->fresh());

    $positions = Reservation::where('book_id', $book->id)
        ->where('status', ReservationStatus::PENDING)
        ->orderBy('queue_position')
        ->pluck('queue_position')
        ->toArray();

    expect($positions)->toBe([1, 2]);
});

it('queue positions are unique across pending reservations', function () {
    $service = makeReservationService();
    $book    = rSvcBook();
    rSvcCopy($book, 'BORROWED');

    $service->reserve(rSvcMember(), $book->id);
    $service->reserve(rSvcMember(), $book->id);
    $service->reserve(rSvcMember(), $book->id);

    $positions = Reservation::where('book_id', $book->id)
        ->where('status', ReservationStatus::PENDING)
        ->pluck('queue_position')
        ->toArray();

    expect(array_unique($positions))->toHaveCount(3);
});

// ─── Repository Tests ─────────────────────────────────────────────────────────

it('repository hasPendingReservation returns true for active reservation', function () {
    $repo   = new ReservationRepository();
    $member = rSvcMember();
    $book   = rSvcBook();
    rSvcReservation($member, $book);

    expect($repo->hasPendingReservation($member, $book))->toBeTrue();
});

it('repository hasPendingReservation returns false after cancellation', function () {
    $repo   = new ReservationRepository();
    $member = rSvcMember();
    $book   = rSvcBook();
    $res    = rSvcReservation($member, $book, ['status' => ReservationStatus::CANCELLED]);

    expect($repo->hasPendingReservation($member, $book))->toBeFalse();
});

it('repository countPending returns correct count', function () {
    $repo = new ReservationRepository();
    $book = rSvcBook();
    $m1   = rSvcMember();
    $m2   = rSvcMember();

    rSvcReservation($m1, $book);
    rSvcReservation($m2, $book);

    expect($repo->countPending($book))->toBe(2);
});

it('repository earliestPending returns lowest queue_position', function () {
    $repo = new ReservationRepository();
    $book = rSvcBook();
    $m1   = rSvcMember();
    $m2   = rSvcMember();

    $r1 = rSvcReservation($m1, $book);
    $r2 = rSvcReservation($m2, $book);

    $earliest = $repo->earliestPending($book);
    expect($earliest->id)->toBe($r1->id);
});

it('repository pendingForBook excludes cancelled and fulfilled', function () {
    $repo   = new ReservationRepository();
    $book   = rSvcBook();
    $member = rSvcMember();

    rSvcReservation($member, $book, ['status' => ReservationStatus::CANCELLED]);
    rSvcReservation($member, $book, ['status' => ReservationStatus::FULFILLED, 'queue_position' => 0]);

    $pending = $repo->pendingForBook($book);
    expect($pending->count())->toBe(0);
});
