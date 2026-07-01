<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe contact card for the property owner/agent. Never exposes email
 * unless the user is staff (the inquiry form is the public contact path).
 */
class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isStaff = $request->user()?->hasRole('agent', 'admin');

        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'role'   => $this->role,
            'avatar' => $this->avatar,
            'phone'  => $this->phone,
            'email'  => $this->when((bool) $isStaff, $this->email),
        ];
    }
}
