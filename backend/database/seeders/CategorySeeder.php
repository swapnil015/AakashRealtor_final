<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'House',       'slug' => 'house',       'icon' => 'home',      'has_rooms' => true],
            ['name' => 'Land',        'slug' => 'land',        'icon' => 'map',       'has_rooms' => false],
            ['name' => 'Flat',        'slug' => 'flat',        'icon' => 'building',  'has_rooms' => true],
            ['name' => 'Apartment',   'slug' => 'apartment',   'icon' => 'buildings', 'has_rooms' => true],
            ['name' => 'Commercial',  'slug' => 'commercial',  'icon' => 'briefcase', 'has_rooms' => true],
            ['name' => 'Residential', 'slug' => 'residential', 'icon' => 'house',     'has_rooms' => true],
        ];

        foreach ($categories as $i => $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c + ['sort_order' => $i]);
        }
    }
}
