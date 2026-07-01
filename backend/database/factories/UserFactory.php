<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'phone'     => '98' . fake()->unique()->numerify('########'),
            'password'  => static::$password ??= Hash::make('password'),
            'role'      => 'user',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function agent(): static
    {
        return $this->state(fn () => ['role' => 'agent']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
