<?php

use App\Enums\Role;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function adminUser(): User
{
    return User::factory()->create(['role' => Role::ADMIN]);
}

function librarianUser(): User
{
    return User::factory()->create(['role' => Role::LIBRARIAN]);
}

function memberUser(): User
{
    return User::factory()->create(['role' => Role::MEMBER]);
}

function makeAuthor(array $attrs = []): Author
{
    return Author::create(array_merge(['name' => 'Default Author', 'bio' => null], $attrs));
}

function makeCategory(array $attrs = []): Category
{
    return Category::create(array_merge(['name' => 'Default Category'], $attrs));
}

function makeBook(array $attrs = []): Book
{
    if (!isset($attrs['author_id'])) {
        $attrs['author_id'] = makeAuthor()->id;
    }
    return Book::create(array_merge([
        'title'          => 'Default Book',
        'description'    => 'A default description for testing.',
        'isbn'           => null,
        'published_year' => 2020,
    ], $attrs));
}

// ─── Books: Index ─────────────────────────────────────────────────────────────

it('shows book catalog for authenticated user', function () {
    $user = memberUser();
    $this->actingAs($user)->get(route('books.index'))->assertStatus(200);
});

it('redirects unauthenticated users from book catalog to login', function () {
    $this->get(route('books.index'))->assertRedirect(route('auth.login'));
});

// ─── Books: Search & Filter ───────────────────────────────────────────────────

it('searches books by title', function () {
    $author = makeAuthor();
    makeBook(['title' => 'Laravel Deep Dive', 'author_id' => $author->id]);
    makeBook(['title' => 'PHP Basics',        'author_id' => $author->id]);

    $user = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['search' => 'Laravel']));

    $response->assertStatus(200)
             ->assertSee('Laravel Deep Dive')
             ->assertDontSee('PHP Basics');
});

it('searches books by ISBN', function () {
    $author = makeAuthor();
    makeBook(['title' => 'ISBN Book', 'isbn' => '978-1234567890', 'author_id' => $author->id]);
    makeBook(['title' => 'No ISBN',   'isbn' => null,             'author_id' => $author->id]);

    $user = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['search' => '978-1234567890']));

    $response->assertStatus(200)->assertSee('ISBN Book');
});

it('searches books by author name', function () {
    $author1 = makeAuthor(['name' => 'Stephen King']);
    $author2 = makeAuthor(['name' => 'J.K. Rowling']);
    makeBook(['title' => 'The Shining', 'author_id' => $author1->id]);
    makeBook(['title' => 'Harry Potter', 'author_id' => $author2->id]);

    $user = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['search' => 'Stephen']));

    $response->assertStatus(200)
             ->assertSee('The Shining')
             ->assertDontSee('Harry Potter');
});

it('filters books by category', function () {
    $author   = makeAuthor();
    $fiction  = makeCategory(['name' => 'Fiction']);
    $science  = makeCategory(['name' => 'Science']);
    $book1    = makeBook(['title' => 'Fiction Book', 'author_id' => $author->id]);
    $book2    = makeBook(['title' => 'Science Book', 'author_id' => $author->id]);
    $book1->categories()->attach($fiction->id);
    $book2->categories()->attach($science->id);

    $user     = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['category' => $fiction->id]));

    $response->assertStatus(200)
             ->assertSee('Fiction Book')
             ->assertDontSee('Science Book');
});

it('sorts books by title ascending', function () {
    $author = makeAuthor();
    makeBook(['title' => 'Zebra',  'author_id' => $author->id]);
    makeBook(['title' => 'Apple',  'author_id' => $author->id]);
    makeBook(['title' => 'Mango',  'author_id' => $author->id]);

    $user     = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['sort_by' => 'title', 'sort_dir' => 'asc']));

    $response->assertStatus(200)->assertSeeInOrder(['Apple', 'Mango', 'Zebra']);
});

it('sorts books by publication year descending', function () {
    $author = makeAuthor();
    makeBook(['title' => 'Old Book', 'published_year' => 2000, 'author_id' => $author->id]);
    makeBook(['title' => 'New Book', 'published_year' => 2023, 'author_id' => $author->id]);

    $user     = memberUser();
    $response = $this->actingAs($user)->get(route('books.index', ['sort_by' => 'published_year', 'sort_dir' => 'desc']));

    $response->assertStatus(200)->assertSeeInOrder(['New Book', 'Old Book']);
});

// ─── Books: Show ──────────────────────────────────────────────────────────────

it('shows book detail page', function () {
    $author = makeAuthor();
    $book   = makeBook(['title' => 'My Book', 'author_id' => $author->id]);

    $user = memberUser();
    $this->actingAs($user)->get(route('books.show', $book->id))
         ->assertStatus(200)
         ->assertSee('My Book');
});

it('returns 404 for non-existent book', function () {
    $user = memberUser();
    $this->actingAs($user)->get(route('books.show', 99999))->assertStatus(404);
});

// ─── Books: Create ────────────────────────────────────────────────────────────

it('admin can access book create page', function () {
    $this->actingAs(adminUser())->get(route('books.create'))->assertStatus(200);
});

it('librarian can access book create page', function () {
    $this->actingAs(librarianUser())->get(route('books.create'))->assertStatus(200);
});

it('member cannot access book create page', function () {
    $this->actingAs(memberUser())->get(route('books.create'))->assertStatus(403);
});

// ─── Books: Store ─────────────────────────────────────────────────────────────

it('admin can create a book', function () {
    $author   = makeAuthor();
    $category = makeCategory();
    $admin    = adminUser();

    $response = $this->actingAs($admin)->post(route('books.store'), [
        'title'          => 'New Novel',
        'description'    => 'A great book.',
        'isbn'           => '978-0-00-111111-1',
        'published_year' => 2022,
        'author_id'      => $author->id,
        'category_ids'   => [$category->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('books', ['title' => 'New Novel', 'isbn' => '978-0-00-111111-1']);
});

it('librarian can create a book', function () {
    $author    = makeAuthor();
    $librarian = librarianUser();

    $response = $this->actingAs($librarian)->post(route('books.store'), [
        'title'     => 'Librarian Book',
        'author_id' => $author->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('books', ['title' => 'Librarian Book']);
});

it('member cannot create a book', function () {
    $author = makeAuthor();
    $member = memberUser();

    $this->actingAs($member)->post(route('books.store'), [
        'title'     => 'Forbidden',
        'author_id' => $author->id,
    ])->assertStatus(403);
});

it('book creation requires title', function () {
    $author = makeAuthor();
    $admin  = adminUser();

    $this->actingAs($admin)->post(route('books.store'), [
        'title'     => '',
        'author_id' => $author->id,
    ])->assertSessionHasErrors('title');
});

it('book creation requires valid author_id', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('books.store'), [
        'title'     => 'No Author',
        'author_id' => 99999,
    ])->assertSessionHasErrors('author_id');
});

it('book creation enforces unique ISBN', function () {
    $author = makeAuthor();
    makeBook(['isbn' => '111-dupe', 'author_id' => $author->id]);
    $admin = adminUser();

    $this->actingAs($admin)->post(route('books.store'), [
        'title'     => 'Another Book',
        'author_id' => $author->id,
        'isbn'      => '111-dupe',
    ])->assertSessionHasErrors('isbn');
});

// ─── Books: Update ────────────────────────────────────────────────────────────

it('admin can update a book', function () {
    $author = makeAuthor();
    $book   = makeBook(['title' => 'Old Title', 'author_id' => $author->id]);
    $admin  = adminUser();

    $this->actingAs($admin)->put(route('books.update', $book->id), [
        'title'     => 'New Title',
        'author_id' => $author->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'New Title']);
});

it('book update allows same ISBN on same book', function () {
    $author = makeAuthor();
    $book   = makeBook(['isbn' => '999-same', 'author_id' => $author->id]);
    $admin  = adminUser();

    $this->actingAs($admin)->put(route('books.update', $book->id), [
        'title'     => $book->title,
        'author_id' => $author->id,
        'isbn'      => '999-same',
    ])->assertSessionDoesntHaveErrors('isbn');
});

it('member cannot update a book', function () {
    $author = makeAuthor();
    $book   = makeBook(['author_id' => $author->id]);
    $member = memberUser();

    $this->actingAs($member)->put(route('books.update', $book->id), [
        'title'     => 'Hacked',
        'author_id' => $author->id,
    ])->assertStatus(403);
});

// ─── Books: Delete ────────────────────────────────────────────────────────────

it('admin can delete a book', function () {
    $author = makeAuthor();
    $book   = makeBook(['author_id' => $author->id]);
    $admin  = adminUser();

    $this->actingAs($admin)->delete(route('books.destroy', $book->id))->assertRedirect(route('books.index'));
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

it('member cannot delete a book', function () {
    $author = makeAuthor();
    $book   = makeBook(['author_id' => $author->id]);
    $member = memberUser();

    $this->actingAs($member)->delete(route('books.destroy', $book->id))->assertStatus(403);
});

// ─── Books: Category Sync ─────────────────────────────────────────────────────

it('creates book with multiple categories', function () {
    $author = makeAuthor();
    $cat1   = makeCategory(['name' => 'Cat A']);
    $cat2   = makeCategory(['name' => 'Cat B']);
    $admin  = adminUser();

    $this->actingAs($admin)->post(route('books.store'), [
        'title'        => 'Multi-Cat Book',
        'author_id'    => $author->id,
        'category_ids' => [$cat1->id, $cat2->id],
    ]);

    $book = Book::where('title', 'Multi-Cat Book')->first();
    expect($book->categories()->count())->toBe(2);
});

it('syncs categories on update', function () {
    $author = makeAuthor();
    $cat1   = makeCategory(['name' => 'Remove Me']);
    $cat2   = makeCategory(['name' => 'Keep Me']);
    $cat3   = makeCategory(['name' => 'Add Me']);
    $book   = makeBook(['author_id' => $author->id]);
    $book->categories()->attach([$cat1->id, $cat2->id]);
    $admin  = adminUser();

    $this->actingAs($admin)->put(route('books.update', $book->id), [
        'title'        => $book->title,
        'author_id'    => $author->id,
        'category_ids' => [$cat2->id, $cat3->id],
    ]);

    $book->refresh();
    $catIds = $book->categories()->pluck('categories.id')->toArray();
    expect($catIds)->toContain($cat2->id)
                   ->toContain($cat3->id)
                   ->not->toContain($cat1->id);
});
