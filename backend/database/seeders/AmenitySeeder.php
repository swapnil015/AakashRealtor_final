<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            'Parking' => 'car', 'Garden' => 'tree', 'Drainage' => 'pipe', 'Water Supply' => 'droplet',
            'Electricity' => 'zap', 'Internet' => 'wifi', 'Security' => 'shield', 'CCTV' => 'video',
            'Lift' => 'elevator', 'Backup Power' => 'battery', 'Modular Kitchen' => 'chef-hat',
            'Furnished' => 'sofa', 'Solar Water' => 'sun', 'Boring Water' => 'droplets',
            'Pet Friendly' => 'paw', 'Lawn' => 'flower',
        ];

        foreach ($amenities as $name => $icon) {
            Amenity::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon]);
        }
    }
}
