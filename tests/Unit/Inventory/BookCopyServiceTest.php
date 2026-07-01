<?php

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Author;
use App\Repositories\BookCopyRepository;
use App\Repositories\BookRepository;
use App\Services\BookCopyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function unitBook(): Book
{
    $author = Author::create(['name' => 'Unit Author', 'bio' => null]);
    return Book::create([
        'title'          => 'Unit Test Book',
        'description'    => '',
        'isbn'           => null,
        'published_year' => 2021,
        'author_id'      => $author->id,
    ]);
}

function unitCopy(Book $book, string $status = 'AVAILABLE'): BookCopy
{
    return $book->copies()->create(['status' => CopyStatus::from($status)]);
}

function makeBookCopyService(): BookCopyService
{
    return new BookCopyService(
        new BookCopyRepository(),
        new BookRepository(),
    );
}

// ─── Availability Calculation ─────────────────────────────────────────────────

it('calculates zero availability for book with no copies', function () {
    $book    = unitBook();
    $service = makeBookCopyService();

    $data = $service->availability($book->id);

    expect($data)->toBe([
        'total'     => 0,
        'available' => 0,
        'borrowed'  => 0,
        'reserved'  => 0,
        'lost'      => 0,
    ]);
});

it('calculates correct availability counts across all statuses', function () {
    $book = unitBook();
    unitCopy($book, 'AVAILABLE');
    unitCopy($book, 'AVAILABLE');
    unitCopy($book, 'BORROWED');
    unitCopy($book, 'RESERVED');
    unitCopy($book, 'LOST');

    $service = makeBookCopyService();
    $data    = $service->availability($book->id);

    expect($data['total'])->toBe(5)
        ->and($data['available'])->toBe(2)
        ->and($data['borrowed'])->toBe(1)
        ->and($data['reserved'])->toBe(1)
        ->and($data['lost'])->toBe(1);
});

it('availability sums total as sum of all status buckets', function () {
    $book = unitBook();
    unitCopy($book, 'AVAILABLE');
    unitCopy($book, 'BORROWED');
    unitCopy($book, 'LOST');

    $service = makeBookCopyService();
    $data    = $service->availability($book->id);

    $summed = $data['available'] + $data['borrowed'] + $data['reserved'] + $data['lost'];
    expect($data['total'])->toBe($summed);
});

// ─── Inventory Synchronization ────────────────────────────────────────────────

it('availability count increases after creating a copy', function () {
    $book    = unitBook();
    $service = makeBookCopyService();

    expect($service->availability($book->id)['total'])->toBe(0);

    $service->createCopy($book->id, ['status' => 'AVAILABLE']);

    expect($service->availability($book->id)['total'])->toBe(1);
});

it('availability count decreases after deleting a copy', function () {
    $book    = unitBook();
    $copy    = unitCopy($book, 'AVAILABLE');
    $service = makeBookCopyService();

    expect($service->availability($book->id)['total'])->toBe(1);

    $service->deleteCopy($book->id, $copy->id);

    expect($service->availability($book->id)['total'])->toBe(0);
});

it('updating a copy status changes availability bucket counts', function () {
    $book    = unitBook();
    $copy    = unitCopy($book, 'AVAILABLE');
    $service = makeBookCopyService();

    expect($service->availability($book->id)['available'])->toBe(1);
    expect($service->availability($book->id)['borrowed'])->toBe(0);

    $service->updateCopy($book->id, $copy->id, ['status' => 'BORROWED']);

    expect($service->availability($book->id)['available'])->toBe(0);
    expect($service->availability($book->id)['borrowed'])->toBe(1);
});

// ─── Status Transition Validation ────────────────────────────────────────────

it('allows AVAILABLE to BORROWED transition', function () {
    $service = makeBookCopyService();

    // No exception should be thrown
    $service->assertValidStatusTransition(CopyStatus::AVAILABLE, CopyStatus::BORROWED);

    expect(true)->toBeTrue();
});

it('allows AVAILABLE to RESERVED transition', function () {
    $service = makeBookCopyService();

    $service->assertValidStatusTransition(CopyStatus::AVAILABLE, CopyStatus::RESERVED);

    expect(true)->toBeTrue();
});

it('allows BORROWED to AVAILABLE transition (return)', function () {
    $service = makeBookCopyService();

    $service->assertValidStatusTransition(CopyStatus::BORROWED, CopyStatus::AVAILABLE);

    expect(true)->toBeTrue();
});

it('allows RESERVED to BORROWED transition (checkout)', function () {
    $service = makeBookCopyService();

    $service->assertValidStatusTransition(CopyStatus::RESERVED, CopyStatus::BORROWED);

    expect(true)->toBeTrue();
});

it('allows any status to LOST transition', function () {
    $service = makeBookCopyService();

    foreach (CopyStatus::cases() as $from) {
        $service->assertValidStatusTransition($from, CopyStatus::LOST);
    }

    expect(true)->toBeTrue();
});

it('rejects LOST to AVAILABLE transition', function () {
    $service = makeBookCopyService();

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $service->assertValidStatusTransition(CopyStatus::LOST, CopyStatus::AVAILABLE);
});

it('rejects LOST to BORROWED transition', function () {
    $service = makeBookCopyService();

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $service->assertValidStatusTransition(CopyStatus::LOST, CopyStatus::BORROWED);
});

// ─── Business Rules ───────────────────────────────────────────────────────────

it('aborts 404 when resolving non-existent book', function () {
    $service = makeBookCopyService();

    $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

    $service->resolveBook(99999);
});

it('aborts 404 when finding non-existent copy for a book', function () {
    $book    = unitBook();
    $service = makeBookCopyService();

    $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

    $service->findCopyOrFail($book, 99999);
});

it('lists all statuses returned by the service', function () {
    $service   = makeBookCopyService();
    $statuses  = $service->statuses();
    $expected  = ['AVAILABLE', 'BORROWED', 'RESERVED', 'LOST'];

    expect($statuses)->toBe($expected);
});

it('new copy belongs to the correct book', function () {
    $book    = unitBook();
    $service = makeBookCopyService();

    $copy = $service->createCopy($book->id, ['status' => 'RESERVED']);

    expect($copy->book_id)->toBe($book->id);
});

it('barcode is unique per copy (virtual from id)', function () {
    $book  = unitBook();
    $copy1 = unitCopy($book, 'AVAILABLE');
    $copy2 = unitCopy($book, 'AVAILABLE');

    expect($copy1->barcode)->not->toBe($copy2->barcode);
});

it('barcode format matches COPY-XXXXX pattern', function () {
    $book = unitBook();
    $copy = unitCopy($book, 'AVAILABLE');

    expect($copy->barcode)->toMatch('/^COPY-\d{5}$/');
});
