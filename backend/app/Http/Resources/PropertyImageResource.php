<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->variants ?? [];

        return [
            'id'         => $this->id,
            'url'        => $this->url ?? ($variants['large'] ?? $variants['medium'] ?? null),
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => $this->sort_order,
            // Responsive sources for <img srcset>.
            'sizes' => [
                'small'  => $variants['small']  ?? null,
                'medium' => $variants['medium'] ?? null,
                'large'  => $variants['large']  ?? null,
                'webp'   => $variants['webp']   ?? null,
            ],
        ];
    }
}
