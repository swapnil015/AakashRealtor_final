<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Http\Resources\AreaResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CityResource;
use App\Models\Amenity;
use App\Models\Area;
use App\Models\Category;
use App\Models\City;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Reference data that powers the search bar, mega-menu and filters.
 * All public, all cacheable.
 */
class LookupController extends Controller
{
    /** GET /api/v1/cities */
    public function cities(Request $request)
    {
        $cities = City::query()
            ->when($request->boolean('popular'), fn ($q) => $q->where('is_popular', true))
            ->withCount(['properties' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(CityResource::collection($cities), 'Cities retrieved.');
    }

    /** GET /api/v1/cities/{city}/areas — {city} bound by public_id. */
    public function areas(City $city)
    {
        return ApiResponse::success(
            AreaResource::collection($city->areas()->orderBy('name')->get()),
            'Areas retrieved.'
        );
    }

    /** GET /api/v1/categories */
    public function categories()
    {
        $categories = Category::query()
            ->withCount(['properties' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories), 'Categories retrieved.');
    }

    /** GET /api/v1/amenities */
    public function amenities()
    {
        return ApiResponse::success(
            AmenityResource::collection(Amenity::orderBy('name')->get()),
            'Amenities retrieved.'
        );
    }
}
