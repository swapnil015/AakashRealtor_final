<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'position' => $this->position,
            'photo'    => $this->photo,
            'socials'  => $this->socials ?? [],
            'branch'   => $this->whenLoaded('branch', fn () => $this->branch?->name),
        ];
    }
}
