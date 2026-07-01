<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $transaction = fake()->randomElement(['buy', 'rent']);
        $title = fake()->randomElement(['Modern', 'Hillside', 'Riverside', 'Heritage', 'Skyline'])
            . ' ' . fake()->randomElement(['Villa', 'Duplex', 'Apartment', 'House', 'Penthouse']);

        $price = $transaction === 'buy'
            ? fake()->numberBetween(5_000_000, 250_000_000)
            : fake()->numberBetween(15_000, 250_000);

        return [
            'user_id'          => User::factory(),
            'category_id'      => Category::factory(),
            'city_id'          => City::factory(),
            'area_id'          => null,
            'title'            => $title,
            'slug'             => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 999999),
            'description'      => fake()->paragraphs(3, true),
            'transaction_type' => $transaction,
            'price'            => $price,
            'price_unit'       => $transaction === 'rent' ? 'per month' : 'total',
            'area_size'        => fake()->randomFloat(2, 2, 30),
            'area_unit'        => 'aana',
            'bedrooms'         => fake()->numberBetween(1, 7),
            'bathrooms'        => fake()->numberBetween(1, 6),
            'floors'           => fake()->numberBetween(1, 5),
            'parking'          => fake()->numberBetween(0, 4),
            'road_width'       => fake()->numberBetween(8, 40),
            'facing'           => fake()->randomElement(['East', 'West', 'North', 'South']),
            'status'           => 'active',
            'latitude'         => fake()->latitude(27.6, 27.8),
            'longitude'        => fake()->longitude(85.2, 85.5),
            'address'          => fake()->streetAddress(),
            'views'            => fake()->numberBetween(0, 5000),
            'published_at'     => now()->subDays(fake()->numberBetween(0, 120)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'published_at' => null]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active', 'published_at' => now()]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function exclusive(): static
    {
        return $this->state(fn () => ['is_exclusive' => true]);
    }

    public function buy(): static
    {
        return $this->state(fn () => ['transaction_type' => 'buy', 'price_unit' => 'total']);
    }

    public function rent(): static
    {
        return $this->state(fn () => ['transaction_type' => 'rent', 'price_unit' => 'per month']);
    }
}
