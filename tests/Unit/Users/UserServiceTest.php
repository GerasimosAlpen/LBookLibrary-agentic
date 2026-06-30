<?php

use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepository::class);
    $this->userService = new UserService($this->userRepository);
});

afterEach(function () {
    Mockery::close();
});

it('allows admin to update any user', function () {
    $admin = User::factory()->make(['id' => 1, 'role' => Role::ADMIN]);
    $data = ['name' => 'Updated'];

    $this->userRepository->shouldReceive('update')
                         ->once()
                         ->with(2, $data)
                         ->andReturn(true);

    $this->userService->updateUser(2, $data, $admin);
});

it('prevents member from updating other user', function () {
    $member = User::factory()->make(['id' => 1, 'role' => Role::MEMBER]);
    $data = ['name' => 'Updated'];

    $this->expectException(ValidationException::class);

    $this->userService->updateUser(2, $data, $member);
});

it('allows member to update themselves', function () {
    $member = User::factory()->make(['id' => 2, 'role' => Role::MEMBER]);
    $data = ['name' => 'Updated'];

    $this->userRepository->shouldReceive('update')
                         ->once()
                         ->with(2, $data)
                         ->andReturn(true);

    $this->userService->updateUser(2, $data, $member);
});
