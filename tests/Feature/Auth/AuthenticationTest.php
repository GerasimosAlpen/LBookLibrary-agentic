<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// ─── Registration ──────────────────────────────────────────────────────────

it('registers a new user with valid data and redirects to dashboard', function () {
    $response = $this->post('/auth/register', [
        'name'                  => 'Alice Smith',
        'email'                 => 'alice@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'name'  => 'Alice Smith',
        'email' => 'alice@example.com',
        'role'  => Role::MEMBER->value,
    ]);
});

it('hashes the password on registration', function () {
    $this->post('/auth/register', [
        'name'                  => 'Bob Jones',
        'email'                 => 'bob@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'bob@example.com')->first();

    expect(Hash::check('secret123', $user->password))->toBeTrue();
});

it('assigns MEMBER role to new users', function () {
    $this->post('/auth/register', [
        'name'                  => 'Carol White',
        'email'                 => 'carol@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'carol@example.com')->first();

    expect($user->role)->toBe(Role::MEMBER);
});

it('authenticates the user after registration', function () {
    $this->post('/auth/register', [
        'name'                  => 'Dan Lee',
        'email'                 => 'dan@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $this->assertAuthenticated();
});

it('rejects registration when email is already taken', function () {
    User::factory()->create(['email' => 'dupe@example.com']);

    $response = $this->post('/auth/register', [
        'name'                  => 'Dupe User',
        'email'                 => 'dupe@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertSessionHasErrors('email');
});

it('rejects registration when password confirmation does not match', function () {
    $response = $this->post('/auth/register', [
        'name'                  => 'Eve Mismatch',
        'email'                 => 'eve@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'wrongpass',
    ]);

    $response->assertSessionHasErrors('password');
});

it('rejects registration when name is missing', function () {
    $response = $this->post('/auth/register', [
        'name'                  => '',
        'email'                 => 'noname@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertSessionHasErrors('name');
});

it('rejects registration when password is too short', function () {
    $response = $this->post('/auth/register', [
        'name'                  => 'Short Pass',
        'email'                 => 'short@example.com',
        'password'              => '1234567',
        'password_confirmation' => '1234567',
    ]);

    $response->assertSessionHasErrors('password');
});

it('shows the register page', function () {
    $this->get('/auth/register')->assertStatus(200);
});

// ─── Login ─────────────────────────────────────────────────────────────────

it('logs in a user with valid credentials and redirects to dashboard', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->post('/auth/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-pass'),
    ]);

    $response = $this->post('/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong-pass',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects login with non-existent email', function () {
    $response = $this->post('/auth/login', [
        'email'    => 'nobody@example.com',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects login when email field is missing', function () {
    $response = $this->post('/auth/login', [
        'email'    => '',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors('email');
});

it('shows the login page', function () {
    $this->get('/auth/login')->assertStatus(200);
});

// ─── Logout ────────────────────────────────────────────────────────────────

it('logs out an authenticated user and redirects to login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/auth/logout');

    $response->assertRedirect(route('auth.login'));
    $this->assertGuest();
});

it('denies logout for unauthenticated requests', function () {
    $response = $this->post('/auth/logout');

    $response->assertRedirect(route('auth.login'));
});

// ─── Password Update ───────────────────────────────────────────────────────

it('updates password successfully with correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->put('/auth/password', [
        'current_password'      => 'old-password',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
});

it('rejects password update when current password is wrong', function () {
    $user = User::factory()->create([
        'password' => Hash::make('real-password'),
    ]);

    $response = $this->actingAs($user)->put('/auth/password', [
        'current_password'      => 'wrong-current',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors('current_password');
});

it('rejects password update when new password confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->put('/auth/password', [
        'current_password'      => 'old-password',
        'password'              => 'new-password-123',
        'password_confirmation' => 'mismatch-pass',
    ]);

    $response->assertSessionHasErrors('password');
});

it('rejects password update when new password is same as current', function () {
    $user = User::factory()->create([
        'password' => Hash::make('same-password'),
    ]);

    $response = $this->actingAs($user)->put('/auth/password', [
        'current_password'      => 'same-password',
        'password'              => 'same-password',
        'password_confirmation' => 'same-password',
    ]);

    $response->assertSessionHasErrors('password');
});

it('requires authentication to access the password update route', function () {
    $response = $this->put('/auth/password', [
        'current_password'      => 'old',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('auth.login'));
});

it('shows the update password page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/auth/password')->assertStatus(200);
});
