<?php

use App\Models\User;
use App\Repositories\AuthRepository;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// ─── Helper ────────────────────────────────────────────────────────────────

function makeAuthService(): AuthService
{
    return new AuthService(new AuthRepository());
}

// ─── Registration ──────────────────────────────────────────────────────────

it('creates a user and returns a User model instance', function () {
    $service = makeAuthService();

    $user = $service->register([
        'name'     => 'Test User',
        'email'    => 'unit@example.com',
        'password' => 'secret123',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('unit@example.com');
});

it('stores the password as a bcrypt hash on registration', function () {
    $service = makeAuthService();

    $user = $service->register([
        'name'     => 'Hash User',
        'email'    => 'hash@example.com',
        'password' => 'plain-text-password',
    ]);

    expect(Hash::check('plain-text-password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('plain-text-password');
});

// ─── Login Credential Verification ────────────────────────────────────────

it('returns a User when credentials are valid', function () {
    $user = User::factory()->create([
        'email'    => 'login@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $service = makeAuthService();

    $result = $service->attemptLogin('login@example.com', 'correct-password');

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

it('returns null when password does not match', function () {
    User::factory()->create([
        'email'    => 'wrongpw@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $service = makeAuthService();

    $result = $service->attemptLogin('wrongpw@example.com', 'wrong-password');

    expect($result)->toBeNull();
});

it('returns null when email does not exist', function () {
    $service = makeAuthService();

    $result = $service->attemptLogin('nobody@example.com', 'anypassword');

    expect($result)->toBeNull();
});

// ─── Password Update ───────────────────────────────────────────────────────

it('updates the password when current password is correct', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-pass'),
    ]);

    $service = makeAuthService();

    $result = $service->updatePassword($user, 'old-pass', 'brand-new-pass');

    expect($result)->toBeTrue();

    $user->refresh();
    expect(Hash::check('brand-new-pass', $user->password))->toBeTrue();
});

it('returns false when current password is incorrect', function () {
    $user = User::factory()->create([
        'password' => Hash::make('real-old-pass'),
    ]);

    $service = makeAuthService();

    $result = $service->updatePassword($user, 'wrong-old-pass', 'brand-new-pass');

    expect($result)->toBeFalse();
});

it('does not change the password when current password is wrong', function () {
    $originalHash = Hash::make('original-pass');

    $user = User::factory()->create([
        'password' => $originalHash,
    ]);

    $service = makeAuthService();

    $service->updatePassword($user, 'bad-current', 'brand-new-pass');

    $user->refresh();
    expect(Hash::check('original-pass', $user->password))->toBeTrue();
});

// ─── AuthRepository ────────────────────────────────────────────────────────

it('finds a user by email', function () {
    $user = User::factory()->create(['email' => 'find@example.com']);

    $repo   = new AuthRepository();
    $result = $repo->findByEmail('find@example.com');

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

it('returns null when email is not found in repository', function () {
    $repo   = new AuthRepository();
    $result = $repo->findByEmail('missing@example.com');

    expect($result)->toBeNull();
});
