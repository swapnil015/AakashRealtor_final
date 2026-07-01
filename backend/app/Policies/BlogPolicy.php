<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function update(User $user, Blog $blog): bool
    {
        return $user->id === $blog->user_id; // admins bypass via Gate::before
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $user->id === $blog->user_id;
    }
}
