<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Property;
use App\Models\User;

it('registers a new user and returns a token', function () {
    $res = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'new@example.com',
        'phone' => '9812345678',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $res->assertCreated();
    expect($res->json('data.token'))->toBeString()
        ->and($res->json('data.user.role'))->toBe('user');
});

it('logs in with email or phone', function () {
    User::factory()->create(['email' => 'a@b.com', 'phone' => '9800000009']);

    expect($this->postJson('/api/v1/auth/login', ['login' => 'a@b.com', 'password' => 'password'])->status())->toBe(200);
    expect($this->postJson('/api/v1/auth/login', ['login' => '9800000009', 'password' => 'password'])->status())->toBe(200);
});

it('rejects bad credentials with 422', function () {
    User::factory()->create(['email' => 'a@b.com']);
    $this->postJson('/api/v1/auth/login', ['login' => 'a@b.com', 'password' => 'wrong'])
        ->assertStatus(422);
});

it('forbids editing a property you do not own', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $p = Property::factory()->create([
        'user_id'     => $owner->id,
        'category_id' => Category::factory(),
        'city_id'     => City::factory(),
    ]);

    $this->actingAs($other)
        ->putJson("/api/v1/properties/{$p->id}", ['title' => 'Hijacked'])
        ->assertStatus(403);
});

it('lets an admin edit any property', function () {
    $admin = User::factory()->admin()->create();
    $p = Property::factory()->create([
        'user_id'     => User::factory(),
        'category_id' => Category::factory(),
        'city_id'     => City::factory(),
    ]);

    $this->actingAs($admin)
        ->putJson("/api/v1/properties/{$p->id}", ['title' => 'Admin Edit'])
        ->assertOk();

    expect(Property::find($p->id)->title)->toBe('Admin Edit');
});

it('blocks unauthenticated property creation', function () {
    $this->postJson('/api/v1/properties', [])->assertStatus(401);
});
