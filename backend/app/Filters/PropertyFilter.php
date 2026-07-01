<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates the entire public listing query (filtering, keyword search and
 * sorting) in one testable place. Each filter is applied only when its param
 * is present, so combinations compose cleanly.
 *
 * Usage:
 *   $query = PropertyFilter::make($request->all())->apply(Property::query());
 */
class PropertyFilter
{
    /** Whitelisted sort modes -> [column, direction]. */
    protected const SORTS = [
        'newest'     => ['published_at', 'desc'],
        'price_asc'  => ['price', 'asc'],
        'price_desc' => ['price', 'desc'],
        'popular'    => ['views', 'desc'],
    ];

    public function __construct(protected array $params = [])
    {
    }

    public static function make(array $params): static
    {
        return new static($params);
    }

    public function apply(Builder $query): Builder
    {
        $this->transactionType($query);
        $this->category($query);
        $this->city($query);
        $this->area($query);
        $this->priceRange($query);
        $this->rooms($query);
        $this->areaRange($query);
        $this->amenities($query);
        $this->keyword($query);
        $this->sort($query);

        return $query;
    }

    /* ── Individual filters ────────────────────────────────────────── */

    protected function transactionType(Builder $q): void
    {
        if ($type = $this->get('transaction_type')) {
            if (in_array($type, ['buy', 'rent'], true)) {
                $q->where('transaction_type', $type);
            }
        }
    }

    protected function category(Builder $q): void
    {
        // Accept category slug (public) or id.
        if ($slug = $this->get('category')) {
            $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug)->orWhere('id', $slug));
        }
    }

    protected function city(Builder $q): void
    {
        // Accept city public_id (from URLs like Kathmandu-53) or slug.
        if ($city = $this->get('city')) {
            $q->whereHas('city', function (Builder $c) use ($city) {
                is_numeric($city)
                    ? $c->where('public_id', (int) $city)
                    : $c->where('slug', $city);
            });
        }
    }

    protected function area(Builder $q): void
    {
        if ($area = $this->get('area')) {
            $q->whereHas('area', fn (Builder $a) => $a->where('slug', $area)->orWhere('id', $area));
        }
    }

    protected function priceRange(Builder $q): void
    {
        if (is_numeric($min = $this->get('min_price'))) {
            $q->where('price', '>=', (float) $min);
        }
        if (is_numeric($max = $this->get('max_price'))) {
            $q->where('price', '<=', (float) $max);
        }
    }

    protected function rooms(Builder $q): void
    {
        if (is_numeric($bd = $this->get('bedrooms'))) {
            $q->where('bedrooms', '>=', (int) $bd);
        }
        if (is_numeric($ba = $this->get('bathrooms'))) {
            $q->where('bathrooms', '>=', (int) $ba);
        }
    }

    protected function areaRange(Builder $q): void
    {
        if (is_numeric($min = $this->get('min_area'))) {
            $q->where('area_size', '>=', (float) $min);
        }
        if (is_numeric($max = $this->get('max_area'))) {
            $q->where('area_size', '<=', (float) $max);
        }
    }

    protected function amenities(Builder $q): void
    {
        $amenities = $this->get('amenities');
        if (empty($amenities)) {
            return;
        }
        $slugs = is_array($amenities) ? $amenities : explode(',', (string) $amenities);
        $slugs = array_filter(array_map('trim', $slugs));

        // Property must have ALL requested amenities (AND semantics).
        foreach ($slugs as $slug) {
            $q->whereHas('amenities', fn (Builder $a) => $a->where('slug', $slug));
        }
    }

    protected function keyword(Builder $q): void
    {
        $term = trim((string) $this->get('q'));
        if ($term === '') {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            // Full-text search against the generated tsvector column.
            $q->whereRaw("searchable @@ plainto_tsquery('simple', ?)", [$term])
              ->orderByRaw("ts_rank(searchable, plainto_tsquery('simple', ?)) DESC", [$term]);
        } else {
            // Portable LIKE fallback (sqlite test runs).
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('title', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('address', 'like', $like);
            });
        }
    }

    protected function sort(Builder $q): void
    {
        $sort = $this->get('sort', 'newest');

        // A keyword search already applied a relevance order; don't override
        // it unless the client explicitly asked for a different sort.
        if ($this->get('q') && ! $this->has('sort')) {
            return;
        }

        [$column, $dir] = self::SORTS[$sort] ?? self::SORTS['newest'];

        // published_at can be null on freshly-approved rows; keep them last.
        if ($column === 'published_at') {
            $q->orderByRaw('published_at IS NULL')->orderBy($column, $dir);
        } else {
            $q->orderBy($column, $dir);
        }

        $q->orderBy('id', 'desc'); // stable tie-breaker for pagination
    }

    /* ── Helpers ───────────────────────────────────────────────────── */

    protected function get(string $key, mixed $default = null): mixed
    {
        $value = $this->params[$key] ?? $default;
        return $value === '' ? $default : $value;
    }

    protected function has(string $key): bool
    {
        return isset($this->params[$key]) && $this->params[$key] !== '';
    }
}
