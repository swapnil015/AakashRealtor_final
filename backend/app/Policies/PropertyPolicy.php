<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

/**
 * Authorization rules for properties. Admins bypass every check via the
 * Gate::before hook in AuthServiceProvider, so these methods only describe
 * the rules for regular users and agents.
 *
 *  - user  : may edit/delete only their OWN listings.
 *  - agent : may manage listings they OWN or are ASSIGNED to (agent_id).
 *  - admin : everything (handled in Gate::before).
 */
class PropertyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // public listing index
    }

    public function view(?User $user, Property $property): bool
    {
        if ($property->status === 'active') {
            return true;
        }

        return $property->isOwnedBy($user) || $property->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Property $property): bool
    {
        if ($property->isOwnedBy($user)) {
            return true;
        }

        return $user->isAgent() && $property->isManagedBy($user);
    }

    public function delete(User $user, Property $property): bool
    {
        // Agents cannot delete; only the owner (or admin) can.
        return $property->isOwnedBy($user);
    }

    /** Approving/rejecting/flagging is admin-only (Gate::before grants it). */
    public function moderate(User $user, Property $property): bool
    {
        return false;
    }
}
