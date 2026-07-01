<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Category;
use App\Models\City;
use App\Models\Faq;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data first.
        $this->call([
            CategorySeeder::class,
            CitySeeder::class,
            AmenitySeeder::class,
        ]);

        // Core accounts.
        $admin = User::updateOrCreate(
            ['email' => 'admin@aakashrealtor.com'],
            [
                'name' => 'Aakash Admin', 'phone' => '9800000001',
                'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true,
            ]
        );

        $agent = User::updateOrCreate(
            ['email' => 'agent@aakashrealtor.com'],
            [
                'name' => 'Valley Agent', 'phone' => '9800000002',
                'password' => Hash::make('password'), 'role' => 'agent', 'is_active' => true,
            ]
        );

        // Skip the heavy demo data when only the essentials are wanted.
        if (app()->environment('production')) {
            return;
        }

        $categories = Category::all();
        $cities = City::with('areas')->get();
        $amenities = Amenity::all();

        // 60 demo listings spread across categories/cities, mostly active.
        // recycle() reuses the already-seeded categories/cities/users instead
        // of the factory creating throwaway ones.
        Property::factory(60)
            ->recycle([$admin, $agent])
            ->recycle($categories)
            ->recycle($cities)
            ->make()
            ->each(function (Property $p) use ($categories, $cities, $amenities) {
                $city = $cities->random();
                $p->forceFill([
                    'user_id'     => fake()->randomElement([1, 2]),
                    'category_id' => $categories->random()->id,
                    'city_id'     => $city->id,
                    'area_id'     => optional($city->areas->random())->id,
                    'status'      => fake()->randomElement(['active', 'active', 'active', 'pending', 'sold']),
                    'is_featured' => fake()->boolean(25),
                    'is_exclusive'=> fake()->boolean(15),
                    'is_emerging' => fake()->boolean(20),
                    'is_open_house' => fake()->boolean(10),
                    'is_by_owner' => fake()->boolean(30),
                ])->save();

                $p->amenities()->sync($amenities->random(rand(3, 7))->pluck('id'));
            });

        // Some demo leads.
        Requirement::factory(12)->create([
            'category_id' => fn () => $categories->random()->id,
            'city_id'     => fn () => $cities->random()->id,
        ]);

        PropertyInquiry::factory(20)->create([
            'property_id' => fn () => Property::inRandomOrder()->value('id'),
        ]);

        // FAQs.
        $faqs = [
            ['q' => 'What is a lalpurja and why does it matter?', 'a' => 'A lalpurja is the official land-ownership certificate in Nepal. We verify every listing against its lalpurja before publishing.'],
            ['q' => 'Do you help NRN buyers?', 'a' => 'Yes — we handle paperwork, lalpurja verification and registration end to end for non-resident Nepali buyers.'],
            ['q' => 'How do I list my property?', 'a' => 'Create an account, click “Post Property”, complete the multi-step form, and our team reviews it before it goes live.'],
            ['q' => 'Are the prices negotiable?', 'a' => 'Many are. Listings marked negotiable can be discussed; submit an inquiry and an agent will reach out.'],
        ];
        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(['question' => $f['q']], ['answer' => $f['a'], 'sort_order' => $i]);
        }
    }
}
