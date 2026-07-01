<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Canonical JSON shape for a property. Relations are included only when
 * eager-loaded (whenLoaded), so list endpoints stay lean and the detail
 * endpoint returns the full graph.
 */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'slug'  => $this->slug,
            'description' => $this->when(! $request->routeIs('*.index'), $this->description),

            'transaction_type' => $this->transaction_type,
            'status'           => $this->status,

            'price' => [
                'amount'     => (float) $this->price,
                'unit'       => $this->price_unit,
                'negotiable' => (bool) $this->price_negotiable,
                'formatted'  => $this->formatPrice(),
            ],

            'area' => [
                'size' => $this->area_size !== null ? (float) $this->area_size : null,
                'unit' => $this->area_unit,
            ],

            'specs' => [
                'bedrooms'   => $this->bedrooms,
                'bathrooms'  => $this->bathrooms,
                'floors'     => $this->floors,
                'parking'    => $this->parking,
                'road_width' => $this->road_width !== null ? (float) $this->road_width : null,
                'facing'     => $this->facing,
            ],

            'flags' => [
                'is_featured'   => (bool) $this->is_featured,
                'is_exclusive'  => (bool) $this->is_exclusive,
                'is_emerging'   => (bool) $this->is_emerging,
                'is_open_house' => (bool) $this->is_open_house,
                'is_by_owner'   => (bool) $this->is_by_owner,
            ],
            'open_house_date' => $this->open_house_date?->toDateString(),

            'location' => [
                'address'   => $this->address,
                'latitude'  => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
                'city'      => new CityResource($this->whenLoaded('city')),
                'area'      => new AreaResource($this->whenLoaded('area')),
            ],

            'category'  => new CategoryResource($this->whenLoaded('category')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'images'    => PropertyImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->primaryImageUrl(),

            'agent' => new AgentResource($this->whenLoaded('agent') ?: $this->whenLoaded('user')),

            'views'        => $this->views,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),

            // SEO-friendly path: /buyHouse/Kathmandu-53 -> detail /property/{slug}
            'url' => '/property/' . $this->slug,

            'similar' => PropertyResource::collection($this->whenLoaded('similar')),
        ];
    }

    /** "Rs. 1.25 Cr" / "Rs. 45,000 /month" — Nepali numbering. */
    protected function formatPrice(): string
    {
        $p = (float) $this->price;
        $suffix = $this->transaction_type === 'rent' ? ' /' . str_replace('per ', '', $this->price_unit) : '';

        if ($this->transaction_type === 'buy') {
            if ($p >= 10000000) {
                return 'Rs. ' . rtrim(rtrim(number_format($p / 10000000, 2), '0'), '.') . ' Cr';
            }
            if ($p >= 100000) {
                return 'Rs. ' . rtrim(rtrim(number_format($p / 100000, 2), '0'), '.') . ' Lakh';
            }
        }

        return 'Rs. ' . number_format($p) . $suffix;
    }

    protected function primaryImageUrl(): ?string
    {
        if (! $this->relationLoaded('images')) {
            return null;
        }
        $img = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        return $img?->url ?? ($img->variants['medium'] ?? null);
    }
}
