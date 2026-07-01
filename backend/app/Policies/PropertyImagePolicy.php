<?php

namespace App\Policies;

use App\Models\PropertyImage;
use App\Models\User;

class PropertyImagePolicy
{
    /** Only the owning property's owner (or assigned agent) may manage images. */
    public function manage(User $user, PropertyImage $image): bool
    {
        $property = $image->property;

        if (! $property) {
            return false;
        }

        return $property->isOwnedBy($user)
            || ($user->isAgent() && $property->isManagedBy($user));
    }

    public function delete(User $user, PropertyImage $image): bool
    {
        return $this->manage($user, $image);
    }

    public function update(User $user, PropertyImage $image): bool
    {
        return $this->manage($user, $image);
    }
}
