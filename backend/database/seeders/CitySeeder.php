<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // public_id is the integer that appears in URLs (e.g. Kathmandu-53).
        $cities = [
            ['public_id' => 53, 'name' => 'Kathmandu', 'district' => 'Kathmandu', 'popular' => true,
             'areas' => ['Budhanilkantha', 'Baluwatar', 'Maharajgunj', 'Baneshwor', 'Kalanki', 'Tokha']],
            ['public_id' => 54, 'name' => 'Lalitpur', 'district' => 'Lalitpur', 'popular' => true,
             'areas' => ['Sanepa', 'Patan', 'Bhaisepati', 'Jhamsikhel', 'Imadol']],
            ['public_id' => 55, 'name' => 'Bhaktapur', 'district' => 'Bhaktapur', 'popular' => true,
             'areas' => ['Suryabinayak', 'Madhyapur Thimi', 'Durbar Square']],
            ['public_id' => 60, 'name' => 'Pokhara', 'district' => 'Kaski', 'popular' => true,
             'areas' => ['Lakeside', 'Lamachaur', 'Bagar']],
            ['public_id' => 70, 'name' => 'Chitwan', 'district' => 'Chitwan', 'popular' => false,
             'areas' => ['Bharatpur', 'Narayangarh']],
            ['public_id' => 80, 'name' => 'Butwal', 'district' => 'Rupandehi', 'popular' => false,
             'areas' => ['Traffic Chowk', 'Golpark']],
        ];

        foreach ($cities as $i => $c) {
            $city = City::updateOrCreate(
                ['public_id' => $c['public_id']],
                [
                    'name'       => $c['name'],
                    'slug'       => Str::slug($c['name']),
                    'district'   => $c['district'],
                    'is_popular' => $c['popular'],
                    'sort_order' => $i,
                ]
            );

            foreach ($c['areas'] as $areaName) {
                Area::updateOrCreate(
                    ['city_id' => $city->id, 'slug' => Str::slug($areaName)],
                    ['name' => $areaName]
                );
            }
        }
    }
}
