<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Repositories\BookRepository;
use App\Services\BookService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new BookRepository();
    $this->service    = new BookService($this->repository);
});

function makeTestAuthor(string $name = 'Test Author'): Author
{
    return Author::create(['name' => $name]);
}

function makeTestCategory(string $name = 'Test Category'): Category
{
    return Category::create(['name' => $name]);
}

// ─── Repository: Paginate ─────────────────────────────────────────────────────

test('BookRepository paginates books', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Alpha', 'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Beta',  'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->paginate();
    expect($result->total())->toBe(2);
});

test('BookRepository searches by title', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Laravel Best Practices', 'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'PHP Cookbook',           'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->paginate(12, 'Laravel');
    expect($result->total())->toBe(1);
    expect($result->items()[0]->title)->toBe('Laravel Best Practices');
});

test('BookRepository filters by category', function () {
    $author = makeTestAuthor();
    $cat    = makeTestCategory('Fiction');
    $book1  = Book::create(['title' => 'Fiction Book', 'description' => 'Desc', 'author_id' => $author->id]);
    $book2  = Book::create(['title' => 'Other Book',   'description' => 'Desc', 'author_id' => $author->id]);
    $book1->categories()->attach($cat->id);

    $result = $this->repository->paginate(12, null, $cat->id);
    expect($result->total())->toBe(1);
    expect($result->items()[0]->title)->toBe('Fiction Book');
});

test('BookRepository sorts by title ascending', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Zorro',  'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Animal', 'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->paginate(12, null, null, 'title', 'asc');
    expect($result->items()[0]->title)->toBe('Animal');
    expect($result->items()[1]->title)->toBe('Zorro');
});

test('BookRepository sorts by title descending', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Zorro',  'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Animal', 'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->paginate(12, null, null, 'title', 'desc');
    expect($result->items()[0]->title)->toBe('Zorro');
});

// ─── Repository: CRUD ─────────────────────────────────────────────────────────

test('BookRepository creates a book', function () {
    $author   = makeTestAuthor();
    $category = makeTestCategory();

    $book = $this->repository->create([
        'title'          => 'Created Book',
        'description'    => 'Desc',
        'isbn'           => '123-456',
        'published_year' => 2021,
        'author_id'      => $author->id,
        'category_ids'   => [$category->id],
    ]);

    expect($book->title)->toBe('Created Book')
        ->and($book->categories()->count())->toBe(1);
});

test('BookRepository updates a book', function () {
    $author = makeTestAuthor();
    $book   = Book::create(['title' => 'Original', 'description' => 'Desc', 'author_id' => $author->id]);

    $updated = $this->repository->update($book, [
        'title'     => 'Updated',
        'author_id' => $author->id,
    ]);

    expect($updated->title)->toBe('Updated');
});

test('BookRepository deletes a book', function () {
    $author = makeTestAuthor();
    $book   = Book::create(['title' => 'Delete Me', 'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->repository->delete($book);
    expect($result)->toBeTrue();
    expect(Book::find($book->id))->toBeNull();
});

// ─── Service: findOrFail ──────────────────────────────────────────────────────

test('BookService findOrFail returns book when it exists', function () {
    $author = makeTestAuthor();
    $book   = Book::create(['title' => 'Findable', 'description' => 'Desc', 'author_id' => $author->id]);

    $found = $this->service->findOrFail($book->id);
    expect($found->id)->toBe($book->id);
});

test('BookService findOrFail aborts 404 for missing book', function () {
    expect(fn () => $this->service->findOrFail(99999))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ─── Service: delete ──────────────────────────────────────────────────────────

test('BookService delete removes the book', function () {
    $author = makeTestAuthor();
    $book   = Book::create(['title' => 'Doomed', 'description' => 'Desc', 'author_id' => $author->id]);

    $this->service->delete($book->id);
    expect(Book::find($book->id))->toBeNull();
});

// ─── Service: list ────────────────────────────────────────────────────────────

test('BookService list returns paginated result', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Book One', 'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->service->list();
    expect($result->total())->toBeGreaterThanOrEqual(1);
});

test('BookService list accepts search parameter', function () {
    $author = makeTestAuthor();
    Book::create(['title' => 'Unique Title XYZ', 'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Other Book ABC',   'description' => 'Desc', 'author_id' => $author->id]);

    $result = $this->service->list(search: 'XYZ');
    expect($result->total())->toBe(1);
});
