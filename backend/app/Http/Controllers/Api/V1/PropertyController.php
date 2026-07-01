<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\PropertyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Jobs\MatchRequirementsToProperty;
use App\Models\Property;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function __construct(protected MediaService $media)
    {
    }

    /**
     * GET /api/v1/properties
     * Public, filtered, sorted, paginated list. Only active listings.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 12), 48);

        $query = Property::query()
            ->active()
            ->with(['city', 'area', 'category', 'images', 'user'])
            ->withCount('amenities');

        PropertyFilter::make($request->all())->apply($query);

        $properties = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            PropertyResource::collection($properties),
            'Properties retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/properties/{property}
     * Single active property by slug; bumps views and attaches similar.
     */
    public function show(Request $request, string $slug)
    {
        $property = Property::query()
            ->where('slug', $slug)
            ->with(['city.areas', 'area', 'category', 'amenities', 'images', 'agent', 'user'])
            ->firstOrFail();

        // Public can only view active listings; owner/agent/admin can preview.
        if ($property->status !== 'active' && ! $request->user()?->hasRole('agent', 'admin')
            && ! $property->isOwnedBy($request->user())) {
            return ApiResponse::error('The requested resource was not found.', 404);
        }

        // Atomic view increment (no model event, avoids race conditions).
        Property::whereKey($property->id)->update(['views' => DB::raw('views + 1')]);
        $property->views++;

        $property->setRelation('similar', $this->similarTo($property));

        return ApiResponse::success(
            new PropertyResource($property),
            'Property retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/properties
     * Authenticated create. Always starts as `pending` for moderation.
     */
    public function store(StorePropertyRequest $request)
    {
        $property = DB::transaction(function () use ($request) {
            $property = $request->user()->properties()->create([
                ...$request->safe()->except(['amenities', 'images']),
                'status' => 'pending',
            ]);

            if ($request->filled('amenities')) {
                $property->amenities()->sync($request->input('amenities'));
            }

            if ($request->hasFile('images')) {
                $this->media->attachMany($property, $request->file('images'));
            }

            return $property;
        });

        $property->load(['city', 'category', 'images', 'amenities', 'user']);

        return ApiResponse::success(
            new PropertyResource($property),
            'Property submitted and is pending review.',
            201
        );
    }

    /**
     * PUT /api/v1/properties/{property}
     * Owner or admin only (PropertyPolicy@update).
     */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        DB::transaction(function () use ($request, $property) {
            $property->update($request->safe()->except(['amenities', 'images']));

            if ($request->has('amenities')) {
                $property->amenities()->sync($request->input('amenities', []));
            }
        });

        $property->load(['city', 'category', 'images', 'amenities', 'user']);

        return ApiResponse::success(
            new PropertyResource($property),
            'Property updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/properties/{property}
     * Owner or admin only. Soft-deletes.
     */
    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $property->delete();

        return ApiResponse::success(null, 'Property deleted successfully.');
    }

    /* ── Homepage section endpoints ────────────────────────────────── */

    public function featured(Request $request)  { return $this->section($request, 'is_featured'); }
    public function exclusive(Request $request)  { return $this->section($request, 'is_exclusive'); }
    public function emerging(Request $request)   { return $this->section($request, 'is_emerging'); }
    public function openHouse(Request $request)  { return $this->section($request, 'is_open_house'); }
    public function byOwner(Request $request)    { return $this->section($request, 'is_by_owner'); }

    /** GET /properties/sold — sold OR rented listings. */
    public function sold(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 12), 48);

        $properties = Property::query()
            ->whereIn('status', ['sold', 'rented'])
            ->with(['city', 'category', 'images', 'user'])
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::success(
            PropertyResource::collection($properties),
            'Sold/rented properties retrieved successfully.'
        );
    }

    /* ── Helpers ───────────────────────────────────────────────────── */

    /** Shared implementation for the boolean-flag section endpoints. */
    protected function section(Request $request, string $flag)
    {
        $perPage = min((int) $request->integer('per_page', 8), 48);

        $properties = Property::query()
            ->active()
            ->where($flag, true)
            ->with(['city', 'category', 'images', 'user'])
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::success(
            PropertyResource::collection($properties),
            'Properties retrieved successfully.'
        );
    }

    /** Up to 4 active listings in the same city + category. */
    protected function similarTo(Property $property)
    {
        return Property::query()
            ->active()
            ->whereKeyNot($property->id)
            ->where('category_id', $property->category_id)
            ->where(fn ($q) => $q
                ->where('city_id', $property->city_id)
                ->orWhere('transaction_type', $property->transaction_type))
            ->with(['city', 'category', 'images', 'user'])
            ->limit(4)
            ->get();
    }
}
