<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getAll()
    {
        return User::all();
    }

    public function findById(int $id)
    {
        return User::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id)
    {
        $user = $this->findById($id);
        $user->delete();
        return true;
    }

    public function getHistory(int $userId)
    {
        $user = $this->findById($userId);
        return $user->transactions()->with('copy.book')->get();
    }
}
