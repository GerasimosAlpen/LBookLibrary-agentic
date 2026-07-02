<?php

use App\Models\Author;
use App\Models\Book;
use App\Repositories\AuthorRepository;
use App\Services\AuthorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new AuthorRepository();
    $this->service    = new AuthorService($this->repository);
});

// ─── Repository ───────────────────────────────────────────────────────────────

test('AuthorRepository paginates authors', function () {
    Author::create(['name' => 'Alice']);
    Author::create(['name' => 'Bob']);

    $result = $this->repository->paginate();
    expect($result->total())->toBe(2);
});

test('AuthorRepository creates an author', function () {
    $author = $this->repository->create(['name' => 'New Author', 'bio' => 'A bio.']);
    expect($author->name)->toBe('New Author')
        ->and($author->bio)->toBe('A bio.');
});

test('AuthorRepository updates an author', function () {
    $author  = Author::create(['name' => 'Before']);
    $updated = $this->repository->update($author, ['name' => 'After']);
    expect($updated->name)->toBe('After');
});

test('AuthorRepository deletes an author', function () {
    $author = Author::create(['name' => 'Doomed']);
    $result = $this->repository->delete($author);
    expect($result)->toBeTrue();
    expect(Author::find($author->id))->toBeNull();
});

test('AuthorRepository findById returns author', function () {
    $author = Author::create(['name' => 'Findable']);
    $found  = $this->repository->findById($author->id);
    expect($found->id)->toBe($author->id);
});

test('AuthorRepository findById returns null for missing id', function () {
    expect($this->repository->findById(99999))->toBeNull();
});

test('AuthorRepository booksPaginate returns books for author', function () {
    $author = Author::create(['name' => 'Prolific']);
    Book::create(['title' => 'Book 1', 'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Book 2', 'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->booksPaginate($author);
    expect($result->total())->toBe(2);
});

// ─── Service ──────────────────────────────────────────────────────────────────

test('AuthorService findOrFail returns existing author', function () {
    $author = Author::create(['name' => 'Target']);
    $found  = $this->service->findOrFail($author->id);
    expect($found->id)->toBe($author->id);
});

test('AuthorService findOrFail throws 404 for missing author', function () {
    expect(fn () => $this->service->findOrFail(99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('AuthorService delete removes author with no books', function () {
    $author = Author::create(['name' => 'Removable']);
    $this->service->delete($author->id);
    expect(Author::find($author->id))->toBeNull();
});

test('AuthorService delete aborts 422 when author has books', function () {
    $author = Author::create(['name' => 'Busy Author']);
    Book::create(['title' => 'Book', 'description' => 'Desc', 'author_id' => $author->id]);

    expect(fn () => $this->service->delete($author->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('AuthorService create persists author', function () {
    $author = $this->service->create(['name' => 'Persisted', 'bio' => null]);
    expect(Author::find($author->id))->not->toBeNull();
});

test('AuthorService update changes author name', function () {
    $author  = Author::create(['name' => 'Original']);
    $updated = $this->service->update($author->id, ['name' => 'Changed']);
    expect($updated->name)->toBe('Changed');
});
