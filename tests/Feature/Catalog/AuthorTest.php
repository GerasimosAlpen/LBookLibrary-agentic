<?php

use App\Enums\Role;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;

// ─── Index ────────────────────────────────────────────────────────────────────

it('shows authors list for authenticated user', function () {
    $user = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($user)->get(route('authors.index'))->assertStatus(200);
});

it('redirects unauthenticated users from authors list', function () {
    $this->get(route('authors.index'))->assertRedirect(route('auth.login'));
});

// ─── Show ─────────────────────────────────────────────────────────────────────

it('shows author detail page', function () {
    $author = Author::create(['name' => 'Jane Doe', 'bio' => 'Famous author.']);
    $user   = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($user)->get(route('authors.show', $author->id))
         ->assertStatus(200)
         ->assertSee('Jane Doe')
         ->assertSee('Famous author.');
});

it('returns 404 for non-existent author', function () {
    $user = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($user)->get(route('authors.show', 99999))->assertStatus(404);
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin can access author create page', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $this->actingAs($admin)->get(route('authors.create'))->assertStatus(200);
});

it('librarian can access author create page', function () {
    $lib = User::factory()->create(['role' => Role::LIBRARIAN]);
    $this->actingAs($lib)->get(route('authors.create'))->assertStatus(200);
});

it('member cannot access author create page', function () {
    $member = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($member)->get(route('authors.create'))->assertStatus(403);
});

// ─── Store ────────────────────────────────────────────────────────────────────

it('admin can create an author', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->post(route('authors.store'), [
        'name' => 'George Orwell',
        'bio'  => 'British author.',
    ])->assertRedirect();

    $this->assertDatabaseHas('authors', ['name' => 'George Orwell']);
});

it('librarian can create an author', function () {
    $lib = User::factory()->create(['role' => Role::LIBRARIAN]);

    $this->actingAs($lib)->post(route('authors.store'), ['name' => 'Lib Author'])
         ->assertRedirect();

    $this->assertDatabaseHas('authors', ['name' => 'Lib Author']);
});

it('member cannot create an author', function () {
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->post(route('authors.store'), ['name' => 'Forbidden'])
         ->assertStatus(403);
});

it('author creation requires name', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->post(route('authors.store'), ['name' => ''])
         ->assertSessionHasErrors('name');
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin can update an author', function () {
    $author = Author::create(['name' => 'Old Name']);
    $admin  = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->put(route('authors.update', $author->id), ['name' => 'New Name'])
         ->assertRedirect();

    $this->assertDatabaseHas('authors', ['id' => $author->id, 'name' => 'New Name']);
});

it('member cannot update an author', function () {
    $author = Author::create(['name' => 'Protected Author']);
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->put(route('authors.update', $author->id), ['name' => 'Hacked'])
         ->assertStatus(403);
});

// ─── Delete ───────────────────────────────────────────────────────────────────

it('admin can delete an author with no books', function () {
    $author = Author::create(['name' => 'Deletable']);
    $admin  = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->delete(route('authors.destroy', $author->id))
         ->assertRedirect(route('authors.index'));

    $this->assertDatabaseMissing('authors', ['id' => $author->id]);
});

it('cannot delete an author who still has books', function () {
    $author = Author::create(['name' => 'Has Books']);
    Book::create([
        'title'       => 'Orphan Book',
        'description' => 'A description.',
        'author_id'   => $author->id,
    ]);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->delete(route('authors.destroy', $author->id))
         ->assertRedirect();

    $this->assertDatabaseHas('authors', ['id' => $author->id]);
});

it('member cannot delete an author', function () {
    $author = Author::create(['name' => 'Protected']);
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->delete(route('authors.destroy', $author->id))
         ->assertStatus(403);
});

// ─── Author Books ─────────────────────────────────────────────────────────────

it('shows books written by an author on author detail page', function () {
    $author = Author::create(['name' => 'Test Author']);
    Book::create(['title' => 'Author Book 1', 'description' => 'Desc', 'author_id' => $author->id]);
    Book::create(['title' => 'Author Book 2', 'description' => 'Desc', 'author_id' => $author->id]);

    $user = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($user)->get(route('authors.show', $author->id))
         ->assertStatus(200)
         ->assertSee('Author Book 1')
         ->assertSee('Author Book 2');
});
