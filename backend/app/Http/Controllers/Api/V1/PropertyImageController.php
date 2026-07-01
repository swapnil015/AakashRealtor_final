<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyImageResource;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class PropertyImageController extends Controller
{
    public function __construct(protected MediaService $media)
    {
    }

    /**
     * POST /api/v1/properties/{property}/images  (auth: owner/agent/admin)
     * Accept multiple images, validate, upload + generate variants.
     */
    public function store(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $max = $this->media->maxPerListing();
        $request->validate([
            'images'   => ['required', 'array', 'max:' . $max],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ], [
            'images.*.max' => 'Each image must be 8 MB or smaller.',
            'images.max'   => "You can upload at most {$max} images per listing.",
        ]);

        $remaining = $max - $property->images()->count();
        if ($remaining <= 0) {
            return ApiResponse::error("This listing already has the maximum of {$max} images.", 422);
        }

        $created = $this->media->attachMany($property, $request->file('images'));

        return ApiResponse::success(
            PropertyImageResource::collection(collect($created)),
            count($created) . ' image(s) uploaded.',
            201
        );
    }

    /**
     * DELETE /api/v1/property-images/{image}  (auth: owner/agent/admin)
     */
    public function destroy(PropertyImage $image)
    {
        $this->authorize('delete', $image);

        $this->media->delete($image);

        return ApiResponse::success(null, 'Image deleted.');
    }

    /**
     * PATCH /api/v1/property-images/{image}/primary  (auth)
     * Promote one image to the listing's primary photo.
     */
    public function setPrimary(PropertyImage $image)
    {
        $this->authorize('update', $image);

        $this->media->setPrimary($image);

        return ApiResponse::success(
            new PropertyImageResource($image->fresh()),
            'Primary image updated.'
        );
    }

    /**
     * PATCH /api/v1/properties/{property}/images/reorder  (auth)
     * Body: { "order": { "<imageId>": 0, "<imageId>": 1, ... } }
     */
    public function reorder(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $validated = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'min:0'],
        ]);

        $this->media->reorder($property, $validated['order']);

        return ApiResponse::success(
            PropertyImageResource::collection($property->images()->get()),
            'Images reordered.'
        );
    }
}
