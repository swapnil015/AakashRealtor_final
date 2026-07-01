<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'public_id', 'name', 'slug', 'district',
        'latitude', 'longitude', 'is_popular', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'latitude'   => 'decimal:7',
            'longitude'  => 'decimal:7',
        ];
    }

    protected string $slugSource = 'name';

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /** Bind {city} route params by the URL public_id (e.g. 53). */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
