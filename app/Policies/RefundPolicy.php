<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view the list of their refunds
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Refund $refund): bool
    {
        // User can view if they received the refund or they own the bot
        return $user->id === $refund->user_id ||
            $user->id === $refund->bot->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only bot owners can initiate refunds
        return true;
    }

    /**
     * Determine whether the user can issue a refund.
     */
    public function issueRefund(User $user, Refund $refund): bool
    {
        // Only bot owners can issue refunds
        return $user->id === $refund->bot->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Refund $refund): bool
    {
        // Only bot owners can update refund details
        return $user->id === $refund->bot->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Refund $refund): bool
    {
        // Only bot owners can delete refund records
        return $user->id === $refund->bot->user_id;
    }
}
