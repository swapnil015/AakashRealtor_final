<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Hide the contact number from the public wall; staff see it.
        $isStaff = $request->user()?->hasRole('agent', 'admin');

        return [
            'id'   => $this->id,
            'name' => $this->name,
            'phone' => $this->when((bool) $isStaff, $this->phone),
            'email' => $this->when((bool) $isStaff, $this->email),
            'transaction_type' => $this->transaction_type,
            'budget' => [
                'min' => $this->min_budget !== null ? (float) $this->min_budget : null,
                'max' => $this->max_budget !== null ? (float) $this->max_budget : null,
            ],
            'message'    => $this->message,
            'status'     => $this->status,
            'category'   => new CategoryResource($this->whenLoaded('category')),
            'city'       => new CityResource($this->whenLoaded('city')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
