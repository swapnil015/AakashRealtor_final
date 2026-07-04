<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Seeder;

/**
 * Gives every image-less property a primary photo (curated Unsplash,
 * rotated per category) so listing grids and the homepage render fully.
 * Idempotent — only touches properties that still have zero images.
 */
class PropertyImageSeeder extends Seeder
{
    /** @var array<string, string[]> Unsplash photo ids per category slug. */
    private array $photos = [
        'house' => [
            '1600596542815-ffad4c1539a9', '1600585154340-be6161a56a0c', '1580587771525-78b9dba3b914',
            '1512917774080-9991f1c4c750', '1568605114967-8130f3a36994', '1613490493576-7fde63acd811',
        ],
        'land' => [
            '1464822759023-fed622ff2c3b', '1500382017468-9049fed747ef', '1524230572899-a752b3835840',
        ],
        'flat' => [
            '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688', '1493809842364-78817add7ffb',
        ],
        'apartment' => [
            '1545324418-cc1a3fa10c00', '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688',
        ],
        'commercial' => [
            '1486406146926-c627a92ad1ab', '1494526585095-c41746248156', '1497366216548-37526070297c',
        ],
        'residential' => [
            '1570129477492-45c003edd2be', '1600607687939-ce8a6c25118c', '1600566753190-17f0baa2a6c3',
        ],
    ];

    public function run(): void
    {
        $counters = [];

        Property::doesntHave('images')->with('category')->get()->each(function (Property $p) use (&$counters) {
            $slug = $p->category->slug ?? 'house';
            $pool = $this->photos[$slug] ?? $this->photos['house'];
            $i = ($counters[$slug] ?? 0) % count($pool);
            $counters[$slug] = ($counters[$slug] ?? 0) + 1;

            $url = fn (int $w) => "https://images.unsplash.com/photo-{$pool[$i]}?auto=format&fit=crop&w={$w}&q=80";

            PropertyImage::create([
                'property_id' => $p->id,
                'path' => "seed/{$pool[$i]}",
                'url' => $url(1400),
                'variants' => [
                    'small' => $url(480),
                    'medium' => $url(900),
                    'large' => $url(1600),
                ],
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        });
    }
}
