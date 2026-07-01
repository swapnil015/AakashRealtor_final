<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'public_id'  => $this->public_id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'district'   => $this->district,
            'is_popular' => (bool) $this->is_popular,
            // URL token used by the frontend, e.g. "Kathmandu-53".
            'url_token'  => $this->name . '-' . $this->public_id,
            'properties_count' => $this->whenCounted('properties'),
            // Closure form: only build the nested collection when the relation
            // is actually loaded (avoids "map on null" when it isn't).
            'areas' => $this->whenLoaded('areas', fn () => AreaResource::collection($this->areas)),
        ];
    }
}
