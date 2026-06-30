<?php

use App\Models\User;
use App\Models\Notification;
use App\Enums\Role;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::MEMBER]);
});

it('allows user to view notifications', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'message' => 'Test Notification',
        'is_read' => false
    ]);

    $this->actingAs($this->user)
         ->get(route('users.notifications'))
         ->assertStatus(200)
         ->assertSee('Test Notification');
});

it('allows user to mark notification as read', function () {
    $notification = Notification::create([
        'user_id' => $this->user->id,
        'message' => 'Test Notification',
        'is_read' => false
    ]);

    $this->actingAs($this->user)
         ->patch(route('users.notifications.read', $notification->id))
         ->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'is_read' => true
    ]);
});

it('prevents user from marking another users notification as read', function () {
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'message' => 'Other Notification',
        'is_read' => false
    ]);

    $this->actingAs($this->user)
         ->patch(route('users.notifications.read', $notification->id))
         ->assertSessionHasErrors('notification');
});
