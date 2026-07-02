<?php

use App\Enums\Role;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;

// ─── Index ────────────────────────────────────────────────────────────────────

it('shows categories list for authenticated user', function () {
    $user = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($user)->get(route('categories.index'))->assertStatus(200);
});

it('redirects unauthenticated users from categories list', function () {
    $this->get(route('categories.index'))->assertRedirect(route('auth.login'));
});

// ─── Show ─────────────────────────────────────────────────────────────────────

it('shows category detail page', function () {
    $cat  = Category::create(['name' => 'Horror']);
    $user = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($user)->get(route('categories.show', $cat->id))
         ->assertStatus(200)
         ->assertSee('Horror');
});

it('returns 404 for non-existent category', function () {
    $user = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($user)->get(route('categories.show', 99999))->assertStatus(404);
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin can access category create page', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $this->actingAs($admin)->get(route('categories.create'))->assertStatus(200);
});

it('member cannot access category create page', function () {
    $member = User::factory()->create(['role' => Role::MEMBER]);
    $this->actingAs($member)->get(route('categories.create'))->assertStatus(403);
});

// ─── Store ────────────────────────────────────────────────────────────────────

it('admin can create a category', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->post(route('categories.store'), ['name' => 'Thriller'])
         ->assertRedirect();

    $this->assertDatabaseHas('categories', ['name' => 'Thriller']);
});

it('librarian can create a category', function () {
    $lib = User::factory()->create(['role' => Role::LIBRARIAN]);

    $this->actingAs($lib)->post(route('categories.store'), ['name' => 'Mystery'])
         ->assertRedirect();

    $this->assertDatabaseHas('categories', ['name' => 'Mystery']);
});

it('member cannot create a category', function () {
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->post(route('categories.store'), ['name' => 'Forbidden'])
         ->assertStatus(403);
});

it('category creation requires name', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->post(route('categories.store'), ['name' => ''])
         ->assertSessionHasErrors('name');
});

it('category name must be unique', function () {
    Category::create(['name' => 'Duplicate']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->post(route('categories.store'), ['name' => 'Duplicate'])
         ->assertSessionHasErrors('name');
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin can update a category', function () {
    $cat   = Category::create(['name' => 'Old Cat']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->put(route('categories.update', $cat->id), ['name' => 'New Cat'])
         ->assertRedirect();

    $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'New Cat']);
});

it('category update allows same name on same category', function () {
    $cat   = Category::create(['name' => 'Same Name']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->put(route('categories.update', $cat->id), ['name' => 'Same Name'])
         ->assertSessionDoesntHaveErrors('name');
});

it('category update enforces unique name across other categories', function () {
    Category::create(['name' => 'Taken']);
    $cat   = Category::create(['name' => 'Mine']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->put(route('categories.update', $cat->id), ['name' => 'Taken'])
         ->assertSessionHasErrors('name');
});

it('member cannot update a category', function () {
    $cat    = Category::create(['name' => 'Protected']);
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->put(route('categories.update', $cat->id), ['name' => 'Hacked'])
         ->assertStatus(403);
});

// ─── Delete ───────────────────────────────────────────────────────────────────

it('admin can delete a category', function () {
    $cat   = Category::create(['name' => 'Gone']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($admin)->delete(route('categories.destroy', $cat->id))
         ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
});

it('member cannot delete a category', function () {
    $cat    = Category::create(['name' => 'Safe']);
    $member = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($member)->delete(route('categories.destroy', $cat->id))
         ->assertStatus(403);
});

// ─── Category Books ───────────────────────────────────────────────────────────

it('shows books belonging to a category on category detail page', function () {
    $author = Author::create(['name' => 'Authorname']);
    $cat    = Category::create(['name' => 'Romance']);
    $book1  = Book::create(['title' => 'Love Story',   'description' => 'Desc', 'author_id' => $author->id]);
    $book2  = Book::create(['title' => 'Other Genre',  'description' => 'Desc', 'author_id' => $author->id]);
    $book1->categories()->attach($cat->id);

    $user = User::factory()->create(['role' => Role::MEMBER]);

    $this->actingAs($user)->get(route('categories.show', $cat->id))
         ->assertStatus(200)
         ->assertSee('Love Story')
         ->assertDontSee('Other Genre');
});

it('deleting a category preserves the books', function () {
    $author = Author::create(['name' => 'Book Author']);
    $cat    = Category::create(['name' => 'Temp Category']);
    $book   = Book::create(['title' => 'Surviving Book', 'description' => 'Desc', 'author_id' => $author->id]);
    $book->categories()->attach($cat->id);

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $this->actingAs($admin)->delete(route('categories.destroy', $cat->id));

    $this->assertDatabaseHas('books', ['id' => $book->id]);
    $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
});
