<?php

namespace App\Services;

use App\Repositories\ReviewRepository;
use Illuminate\Validation\ValidationException;
use App\Models\Book;

class ReviewService
{
    protected $reviewRepository;

    public function __construct(ReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function getReviewsForBook(int $bookId)
    {
        return $this->reviewRepository->getByBookId($bookId);
    }

    public function createReview(int $bookId, array $data, $currentUser)
    {
        $book = Book::find($bookId);
        if (!$book) {
            throw ValidationException::withMessages(['book' => 'Book not found']);
        }

        if ($this->reviewRepository->existsForUserAndBook($currentUser->id, $bookId)) {
            throw ValidationException::withMessages(['review' => 'You have already reviewed this book']);
        }

        $data['user_id'] = $currentUser->id;
        $data['book_id'] = $bookId;

        return $this->reviewRepository->create($data);
    }

    public function deleteReview(int $bookId, int $reviewId, $currentUser)
    {
        $review = $this->reviewRepository->findById($reviewId);

        if ($currentUser->role->value !== 'ADMIN' && $currentUser->id !== $review->user_id) {
            throw ValidationException::withMessages(['review' => 'Unauthorized review deletion']);
        }

        return $this->reviewRepository->delete($reviewId);
    }
}
