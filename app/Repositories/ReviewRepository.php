<?php

namespace App\Repositories;

use App\Models\Review;

class ReviewRepository
{
    public function getByBookId(int $bookId)
    {
        return Review::where('book_id', $bookId)->with('user')->latest()->get();
    }

    public function create(array $data)
    {
        return Review::create($data);
    }

    public function findById(int $id)
    {
        return Review::findOrFail($id);
    }

    public function delete(int $id)
    {
        $review = $this->findById($id);
        $review->delete();
        return true;
    }

    public function existsForUserAndBook(int $userId, int $bookId): bool
    {
        return Review::where('user_id', $userId)->where('book_id', $bookId)->exists();
    }
}
