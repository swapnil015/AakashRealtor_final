<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generates a unique slug from a source column on create (and when the
 * source changes) — e.g. "Hillside Glass Villa" -> "hillside-glass-villa",
 * appending -2, -3 … on collision.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->slugSource ?? 'name';

            if (empty($model->slug) || $model->isDirty($source) && empty($model->getOriginal('slug'))) {
                $model->slug = $model->generateUniqueSlug($model->{$source});
            }
        });
    }

    protected function generateUniqueSlug(?string $value): string
    {
        $base = Str::slug($value ?: Str::random(8));
        $slug = $base;
        $i = 2;

        while (
            static::withTrashedIfAvailable()
                ->where('slug', $slug)
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** Include soft-deleted rows in the uniqueness check when supported. */
    protected static function withTrashedIfAvailable()
    {
        $query = static::query();

        if (method_exists(static::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query;
    }
}
