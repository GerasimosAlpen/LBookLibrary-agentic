<?php

use App\Services\ReviewService;
use App\Repositories\ReviewRepository;
use App\Models\User;
use App\Models\Review;
use App\Enums\Role;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->reviewRepository = Mockery::mock(ReviewRepository::class);
    $this->reviewService = new ReviewService($this->reviewRepository);
});

afterEach(function () {
    Mockery::close();
});

it('allows admin to delete any review', function () {
    $admin = User::factory()->make(['id' => 1, 'role' => Role::ADMIN]);
    $review = new Review(['id' => 10, 'user_id' => 2]);

    $this->reviewRepository->shouldReceive('findById')
                           ->once()
                           ->with(10)
                           ->andReturn($review);

    $this->reviewRepository->shouldReceive('delete')
                           ->once()
                           ->with(10)
                           ->andReturn(true);

    $this->reviewService->deleteReview(5, 10, $admin);
});

it('prevents user from deleting someone else review', function () {
    $user = User::factory()->make(['id' => 1, 'role' => Role::MEMBER]);
    $review = new Review(['id' => 10, 'user_id' => 2]);

    $this->reviewRepository->shouldReceive('findById')
                           ->once()
                           ->with(10)
                           ->andReturn($review);

    $this->expectException(ValidationException::class);

    $this->reviewService->deleteReview(5, 10, $user);
});
