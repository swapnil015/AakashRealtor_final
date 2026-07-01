<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug', 'icon'];

    protected string $slugSource = 'name';

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'amenity_property');
    }
}
