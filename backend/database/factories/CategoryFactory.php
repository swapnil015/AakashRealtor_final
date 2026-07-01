<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['House', 'Land', 'Flat', 'Apartment', 'Commercial', 'Residential'])
            . ' ' . fake()->unique()->numberBetween(1, 999999);

        return [
            'name'      => $name,
            'slug'      => Str::slug($name),
            'has_rooms' => true,
        ];
    }
}
