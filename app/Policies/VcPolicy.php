<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vc;

class VcPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vc $vc): bool
    {
        return $user->id === $vc->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vc $vc): bool
    {
        return $user->id === $vc->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vc $vc): bool
    {
        return $user->id === $vc->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vc $vc): bool
    {
        return $user->id === $vc->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vc $vc): bool
    {
        return $user->id === $vc->user_id;
    }
}
