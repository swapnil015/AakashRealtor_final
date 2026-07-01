<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyInquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name'        => fake()->name(),
            'phone'       => '98' . fake()->numerify('########'),
            'email'       => fake()->safeEmail(),
            'message'     => fake()->sentence(),
            'status'      => 'new',
        ];
    }
}
