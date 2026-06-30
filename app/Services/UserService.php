<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\Book;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }

    public function getUser(int $id)
    {
        return $this->userRepository->findById($id);
    }

    public function updateUser(int $id, array $data, $currentUser)
    {
        if ($currentUser->role->value !== 'ADMIN' && $currentUser->id !== $id) {
            throw ValidationException::withMessages(['user' => 'Unauthorized profile modification']);
        }
        
        return $this->userRepository->update($id, $data);
    }

    public function deleteUser(int $id, $currentUser)
    {
        if ($currentUser->role->value !== 'ADMIN') {
            throw ValidationException::withMessages(['user' => 'Unauthorized deletion']);
        }

        return $this->userRepository->delete($id);
    }

    public function getBorrowingHistory(int $userId)
    {
        return $this->userRepository->getHistory($userId);
    }

    public function getRecommendations(int $userId)
    {
        // Simple recommendation logic based on categories the user has borrowed.
        $history = $this->getBorrowingHistory($userId);
        
        if ($history->isEmpty()) {
            return Book::inRandomOrder()->take(5)->get();
        }

        $categories = collect();
        foreach ($history as $transaction) {
            if ($transaction->copy && $transaction->copy->book) {
                $categories = $categories->merge($transaction->copy->book->categories->pluck('id'));
            }
        }

        if ($categories->isEmpty()) {
            return Book::inRandomOrder()->take(5)->get();
        }

        return Book::whereHas('categories', function($q) use ($categories) {
            $q->whereIn('categories.id', $categories->unique());
        })
        ->whereDoesntHave('copies.transactions', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with('author')
        ->inRandomOrder()
        ->take(5)
        ->get();
    }
}
