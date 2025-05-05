<?php

namespace App\Policies;

use App\Models\Balance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BalancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view the list of their balances
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Balance $balance): bool
    {
        // User can view if they own the balance or they own the bot
        return $user->id === $balance->user_id ||
            $user->id === $balance->bot->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only bot owners can create balances
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Balance $balance): bool
    {
        // Only bot owners can update balances
        return $user->id === $balance->bot->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Balance $balance): bool
    {
        // Only bot owners can delete balances
        return $user->id === $balance->bot->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Balance $balance): bool
    {
        // Only bot owners can restore balances
        return $user->id === $balance->bot->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Balance $balance): bool
    {
        // Only bot owners can force delete balances
        return $user->id === $balance->bot->user_id;
    }
}
