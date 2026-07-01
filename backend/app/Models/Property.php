<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    /** Homepage placement flags, reused by section endpoints + admin. */
    public const FLAGS = ['is_featured', 'is_exclusive', 'is_emerging', 'is_open_house', 'is_by_owner'];

    protected $fillable = [
        'user_id', 'category_id', 'city_id', 'area_id', 'agent_id',
        'title', 'slug', 'description',
        'transaction_type', 'price', 'price_unit', 'price_negotiable',
        'area_size', 'area_unit',
        'bedrooms', 'bathrooms', 'floors', 'parking', 'road_width', 'facing',
        'status', 'rejection_reason',
        'is_featured', 'is_exclusive', 'is_emerging', 'is_open_house', 'is_by_owner', 'open_house_date',
        'latitude', 'longitude', 'address',
        'views', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price'            => 'decimal:2',
            'area_size'        => 'decimal:2',
            'road_width'       => 'decimal:2',
            'latitude'         => 'decimal:7',
            'longitude'        => 'decimal:7',
            'price_negotiable' => 'boolean',
            'is_featured'      => 'boolean',
            'is_exclusive'     => 'boolean',
            'is_emerging'      => 'boolean',
            'is_open_house'    => 'boolean',
            'is_by_owner'      => 'boolean',
            'open_house_date'  => 'date',
            'published_at'     => 'datetime',
        ];
    }

    /** Column the slug is derived from. */
    protected string $slugSource = 'title';

    /* ── Relationships ─────────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->where('is_primary', true);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property');
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /* ── Query scopes ──────────────────────────────────────────────── */

    /** Only publicly visible listings. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function scopeTransaction(Builder $q, ?string $type): Builder
    {
        return $type ? $q->where('transaction_type', $type) : $q;
    }

    /** Resolve route-model binding by slug. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ── Domain helpers ────────────────────────────────────────────── */

    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->user_id === $user->id;
    }

    public function isManagedBy(?User $user): bool
    {
        return $user && $this->agent_id === $user->id;
    }

    /** Publish: flip to active and stamp the publish time once. */
    public function markActive(): void
    {
        $this->forceFill([
            'status'       => 'active',
            'published_at' => $this->published_at ?? now(),
        ])->save();
    }
}
