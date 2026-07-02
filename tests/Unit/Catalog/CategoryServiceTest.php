<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new CategoryRepository();
    $this->service    = new CategoryService($this->repository);
});

// ─── Repository ───────────────────────────────────────────────────────────────

test('CategoryRepository paginates categories', function () {
    Category::create(['name' => 'Fiction']);
    Category::create(['name' => 'Non-Fiction']);

    $result = $this->repository->paginate();
    expect($result->total())->toBe(2);
});

test('CategoryRepository creates a category', function () {
    $cat = $this->repository->create(['name' => 'Horror']);
    expect($cat->name)->toBe('Horror');
});

test('CategoryRepository updates a category', function () {
    $cat     = Category::create(['name' => 'Old']);
    $updated = $this->repository->update($cat, ['name' => 'New']);
    expect($updated->name)->toBe('New');
});

test('CategoryRepository deletes a category', function () {
    $cat    = Category::create(['name' => 'Gone']);
    $result = $this->repository->delete($cat);
    expect($result)->toBeTrue();
    expect(Category::find($cat->id))->toBeNull();
});

test('CategoryRepository findById returns category', function () {
    $cat   = Category::create(['name' => 'Found']);
    $found = $this->repository->findById($cat->id);
    expect($found->id)->toBe($cat->id);
});

test('CategoryRepository findById returns null for missing id', function () {
    expect($this->repository->findById(99999))->toBeNull();
});

test('CategoryRepository booksPaginate returns books for category', function () {
    $author = Author::create(['name' => 'Author For Cat']);
    $cat    = Category::create(['name' => 'Science']);
    $book1  = Book::create(['title' => 'Sci Book 1', 'description' => 'Desc', 'author_id' => $author->id]);
    $book2  = Book::create(['title' => 'Sci Book 2', 'description' => 'Desc', 'author_id' => $author->id]);
    $book1->categories()->attach($cat->id);
    $book2->categories()->attach($cat->id);

    $result = $this->repository->booksPaginate($cat);
    expect($result->total())->toBe(2);
});

test('CategoryRepository all returns collection ordered by name', function () {
    Category::create(['name' => 'Zebra']);
    Category::create(['name' => 'Alpha']);

    $all = $this->repository->all();
    expect($all->first()->name)->toBe('Alpha');
});

// ─── Service ──────────────────────────────────────────────────────────────────

test('CategoryService findOrFail returns existing category', function () {
    $cat   = Category::create(['name' => 'Target']);
    $found = $this->service->findOrFail($cat->id);
    expect($found->id)->toBe($cat->id);
});

test('CategoryService findOrFail throws 404 for missing category', function () {
    expect(fn () => $this->service->findOrFail(99999))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('CategoryService delete removes category and detaches books', function () {
    $author = Author::create(['name' => 'Author']);
    $cat    = Category::create(['name' => 'Removable']);
    $book   = Book::create(['title' => 'Affected Book', 'description' => 'Desc', 'author_id' => $author->id]);
    $book->categories()->attach($cat->id);

    $this->service->delete($cat->id);

    expect(Category::find($cat->id))->toBeNull();
    expect(Book::find($book->id))->not->toBeNull();
    expect($book->fresh()->categories()->count())->toBe(0);
});

test('CategoryService create persists category', function () {
    $cat = $this->service->create(['name' => 'Persisted']);
    expect(Category::find($cat->id))->not->toBeNull();
});

test('CategoryService update changes category name', function () {
    $cat     = Category::create(['name' => 'Original']);
    $updated = $this->service->update($cat->id, ['name' => 'Changed']);
    expect($updated->name)->toBe('Changed');
});

test('CategoryService list returns paginated categories', function () {
    Category::create(['name' => 'Listable']);
    $result = $this->service->list();
    expect($result->total())->toBeGreaterThanOrEqual(1);
});
