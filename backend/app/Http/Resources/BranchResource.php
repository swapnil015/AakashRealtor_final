<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'address'        => $this->address,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'map_url'        => $this->map_url,
            'is_head_office' => (bool) $this->is_head_office,
            'city'           => new CityResource($this->whenLoaded('city')),
        ];
    }
}
