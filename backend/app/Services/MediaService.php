<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Handles property image uploads across the configured disk (Cloudinary, S3
 * or local "public"). Produces responsive variants (small/medium/large + webp)
 * and keeps the per-listing image cap and a single primary image.
 *
 * The actual resize uses intervention/image when available; if the extension
 * isn't installed the original is stored and variants point at it, so the API
 * never hard-fails on a missing optional dependency.
 */
class MediaService
{
    /** Longest edge (px) for each generated size. */
    protected const SIZES = ['small' => 480, 'medium' => 1024, 'large' => 1600];

    public function disk(): string
    {
        return config('filesystems.media', 'public');
    }

    public function maxPerListing(): int
    {
        return (int) env('MAX_IMAGES_PER_LISTING', 20);
    }

    /**
     * Attach multiple uploaded files to a property, respecting the cap.
     *
     * @param  UploadedFile[]  $files
     * @return PropertyImage[]
     */
    public function attachMany(Property $property, array $files): array
    {
        $existing = $property->images()->count();
        $room = max(0, $this->maxPerListing() - $existing);
        $files = array_slice(array_values($files), 0, $room);

        $created = [];
        foreach ($files as $i => $file) {
            $created[] = $this->attachOne($property, $file, $existing + $i);
        }

        // Guarantee exactly one primary image exists.
        $this->ensurePrimary($property);

        return $created;
    }

    public function attachOne(Property $property, UploadedFile $file, int $sortOrder = 0): PropertyImage
    {
        $variants = $this->store($file, $property->id);

        return $property->images()->create([
            'path'       => $variants['_path'],
            'url'        => $variants['large'] ?? $variants['original'] ?? null,
            'variants'   => array_diff_key($variants, ['_path' => null]),
            'is_primary' => $property->images()->where('is_primary', true)->doesntExist(),
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Store an image and its variants on the media disk.
     * Returns a map: [small, medium, large, webp, original, _path].
     */
    protected function store(UploadedFile $file, int $propertyId): array
    {
        $dir = "properties/{$propertyId}";
        $base = Str::uuid()->toString();
        $disk = Storage::disk($this->disk());
        $out = [];

        // Try to build resized + webp variants with intervention/image.
        if ($this->canManipulate()) {
            try {
                $manager = new \Intervention\Image\ImageManager(
                    new \Intervention\Image\Drivers\Gd\Driver()
                );

                foreach (self::SIZES as $name => $edge) {
                    $img = $manager->read($file->getRealPath())->scaleDown($edge, $edge);
                    $path = "{$dir}/{$base}-{$name}.jpg";
                    $disk->put($path, (string) $img->toJpeg(82), 'public');
                    $out[$name] = $disk->url($path);
                }

                // A single webp at medium size for modern browsers.
                $webp = $manager->read($file->getRealPath())->scaleDown(1024, 1024);
                $webpPath = "{$dir}/{$base}-medium.webp";
                $disk->put($webpPath, (string) $webp->toWebp(80), 'public');
                $out['webp'] = $disk->url($webpPath);

                $out['_path'] = "{$dir}/{$base}-large.jpg";

                return $out;
            } catch (Throwable $e) {
                // Fall through to storing the original untouched.
            }
        }

        // Fallback: store the original; point all sizes at it.
        $path = $disk->putFileAs($dir, $file, "{$base}.{$file->getClientOriginalExtension()}", 'public');
        $url = $disk->url($path);

        return [
            'small' => $url, 'medium' => $url, 'large' => $url,
            'original' => $url, '_path' => $path,
        ];
    }

    public function delete(PropertyImage $image): void
    {
        $disk = Storage::disk($this->disk());

        // Remove every stored variant we can resolve back to a path.
        foreach (['_path', 'small', 'medium', 'large', 'webp'] as $key) {
            $candidate = $key === '_path' ? $image->path : ($image->variants[$key] ?? null);
            if ($candidate) {
                $path = $this->urlToPath($candidate);
                if ($path && $disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }

        $wasPrimary = $image->is_primary;
        $property = $image->property;
        $image->delete();

        if ($wasPrimary && $property) {
            $this->ensurePrimary($property);
        }
    }

    /** Promote a single image to primary, demoting the rest. */
    public function setPrimary(PropertyImage $image): void
    {
        $image->property->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
    }

    /** Apply a new ordering: [imageId => sortOrder]. */
    public function reorder(Property $property, array $order): void
    {
        foreach ($order as $id => $position) {
            $property->images()->whereKey($id)->update(['sort_order' => (int) $position]);
        }
    }

    protected function ensurePrimary(Property $property): void
    {
        if ($property->images()->where('is_primary', true)->exists()) {
            return;
        }
        $first = $property->images()->orderBy('sort_order')->first();
        $first?->update(['is_primary' => true]);
    }

    protected function canManipulate(): bool
    {
        return class_exists(\Intervention\Image\ImageManager::class) && extension_loaded('gd');
    }

    protected function urlToPath(string $url): ?string
    {
        // Local "public" disk URLs are .../storage/<path>.
        if (str_contains($url, '/storage/')) {
            return Str::after($url, '/storage/');
        }
        // For remote disks the stored `path` is already the key.
        return null;
    }
}
