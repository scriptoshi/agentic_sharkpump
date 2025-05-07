<?php

namespace App\Policies;

use App\Models\TelegramUpdate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TelegramUpdatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own updates
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TelegramUpdate $telegramUpdate): bool
    {
        return $user->id === $telegramUpdate->user_id || 
               $user->id === $telegramUpdate->bot->user_id ||
               $user->is_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Webhook is responsible for creating updates, not users
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TelegramUpdate $telegramUpdate): bool
    {
        return $user->id === $telegramUpdate->user_id || 
               $user->id === $telegramUpdate->bot->user_id ||
               $user->is_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TelegramUpdate $telegramUpdate): bool
    {
        return $user->id === $telegramUpdate->user_id || 
               $user->id === $telegramUpdate->bot->user_id ||
               $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TelegramUpdate $telegramUpdate): bool
    {
        return $user->id === $telegramUpdate->user_id || 
               $user->id === $telegramUpdate->bot->user_id ||
               $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TelegramUpdate $telegramUpdate): bool
    {
        return $user->is_admin;
    }
}
