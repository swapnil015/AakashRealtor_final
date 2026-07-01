<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'property_id' => $this->property_id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'message'     => $this->message,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toIso8601String(),
            'property'    => new PropertyResource($this->whenLoaded('property')),
        ];
    }
}
