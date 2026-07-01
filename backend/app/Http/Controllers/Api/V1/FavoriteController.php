<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /** GET /api/v1/favorites  (auth) — the user's saved listings. */
    public function index(Request $request)
    {
        $properties = Property::query()
            ->whereIn('id', $request->user()->favorites()->pluck('property_id'))
            ->with(['city', 'category', 'images', 'user'])
            ->latest()
            ->paginate(12);

        return ApiResponse::success(
            PropertyResource::collection($properties),
            'Favorites retrieved.'
        );
    }

    /** POST /api/v1/favorites/{property}  (auth) — toggle save/unsave. */
    public function toggle(Request $request, Property $property)
    {
        $user = $request->user();
        $existing = $user->favorites()->where('property_id', $property->id)->first();

        if ($existing) {
            $existing->delete();
            return ApiResponse::success(['favorited' => false], 'Removed from favorites.');
        }

        $user->favorites()->create(['property_id' => $property->id]);

        return ApiResponse::success(['favorited' => true], 'Saved to favorites.');
    }
}
