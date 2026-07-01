<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequirementFactory extends Factory
{
    public function definition(): array
    {
        $min = fake()->numberBetween(1_000_000, 10_000_000);

        return [
            'user_id'          => null,
            'name'             => fake()->name(),
            'phone'            => '98' . fake()->numerify('########'),
            'email'            => fake()->safeEmail(),
            'category_id'      => Category::factory(),
            'city_id'          => City::factory(),
            'transaction_type' => fake()->randomElement(['buy', 'rent']),
            'min_budget'       => $min,
            'max_budget'       => $min + fake()->numberBetween(1_000_000, 50_000_000),
            'message'          => fake()->sentence(),
            'status'           => 'open',
        ];
    }
}
