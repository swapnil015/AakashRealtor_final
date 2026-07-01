<?php

namespace App\Policies;

use App\Models\PropertyInquiry;
use App\Models\User;

/**
 * Inquiries are created publicly. Reading/updating them is staff-only:
 * admins (via Gate::before) plus the agent who owns/manages the listing.
 */
class PropertyInquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('agent', 'admin');
    }

    public function view(User $user, PropertyInquiry $inquiry): bool
    {
        $property = $inquiry->property;

        return $user->isAgent()
            && $property
            && ($property->isOwnedBy($user) || $property->isManagedBy($user));
    }

    public function update(User $user, PropertyInquiry $inquiry): bool
    {
        return $this->view($user, $inquiry);
    }
}
