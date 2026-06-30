<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Review;
use App\Enums\Role;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::MEMBER]);
    $this->admin = User::factory()->create(['role' => Role::ADMIN]);
    $this->book = Book::factory()->create();
});

it('allows user to review a book', function () {
    $this->actingAs($this->user)
         ->post(route('books.reviews.store', $this->book->id), [
             'rating' => 5,
             'comment' => 'Great book!'
         ])
         ->assertRedirect();

    $this->assertDatabaseHas('reviews', [
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'rating' => 5
    ]);
});

it('prevents user from reviewing the same book twice', function () {
    Review::create([
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'rating' => 4,
        'comment' => 'Good'
    ]);

    $this->actingAs($this->user)
         ->post(route('books.reviews.store', $this->book->id), [
             'rating' => 5,
             'comment' => 'Another review'
         ])
         ->assertSessionHasErrors('review');
});

it('allows user to delete their own review', function () {
    $review = Review::create([
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'rating' => 4,
        'comment' => 'Good'
    ]);

    $this->actingAs($this->user)
         ->delete(route('books.reviews.destroy', ['id' => $this->book->id, 'reviewId' => $review->id]))
         ->assertRedirect();

    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});

it('prevents user from deleting someone else review', function () {
    $otherUser = User::factory()->create();
    $review = Review::create([
        'user_id' => $otherUser->id,
        'book_id' => $this->book->id,
        'rating' => 4,
    ]);

    $this->actingAs($this->user)
         ->delete(route('books.reviews.destroy', ['id' => $this->book->id, 'reviewId' => $review->id]))
         ->assertSessionHasErrors('review');
});

it('allows admin to delete any review', function () {
    $review = Review::create([
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'rating' => 4,
    ]);

    $this->actingAs($this->admin)
         ->delete(route('books.reviews.destroy', ['id' => $this->book->id, 'reviewId' => $review->id]))
         ->assertRedirect();

    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});
