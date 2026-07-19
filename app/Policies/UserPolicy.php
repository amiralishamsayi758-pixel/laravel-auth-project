<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->isAdmin() || $user->is($targetUser);
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->isAdmin() || $user->is($targetUser);
    }

    public function changeRole(User $user, User $targetUser): bool
    {
        return $user->isAdmin() && ! $user->is($targetUser);
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->isAdmin() && ! $user->is($targetUser);
    }
}
