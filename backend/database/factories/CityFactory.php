<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CityFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'public_id' => fake()->unique()->numberBetween(10, 9999),
            'name'      => $name,
            'slug'      => Str::slug($name),
            'district'  => fake()->city(),
            'latitude'  => fake()->latitude(26.5, 30.4),
            'longitude' => fake()->longitude(80.0, 88.2),
            'is_popular'=> false,
        ];
    }

    public function popular(): static
    {
        return $this->state(fn () => ['is_popular' => true]);
    }
}
