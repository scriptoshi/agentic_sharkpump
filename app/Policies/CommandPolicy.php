<?php

namespace App\Policies;

use App\Models\Command;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommandPolicy
{
    use HandlesAuthorization;

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
    public function view(User $user, Command $command): bool
    {
        return $user->id === $command->user_id;
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
    public function update(User $user, Command $command): bool
    {
        return $user->id === $command->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Command $command): bool
    {
        return $user->id === $command->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Command $command): bool
    {
        return $user->id === $command->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Command $command): bool
    {
        return $user->id === $command->user_id;
    }
}
