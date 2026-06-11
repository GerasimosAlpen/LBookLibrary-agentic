<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository
    ) {}

    public function register(array $data): User
    {
        return $this->authRepository->create($data);
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        $user = $this->authRepository->findByEmail($email);

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        return $this->authRepository->updatePassword($user, $newPassword);
    }
}
