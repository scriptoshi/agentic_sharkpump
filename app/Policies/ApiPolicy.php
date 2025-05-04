<?php

namespace App\Policies;

use App\Models\Api;
use App\Models\User;

class ApiPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, Api $api): bool
    {
        return $user->id === $api->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Api $api): bool
    {
        return $user->id === $api->user_id;
    }

    public function delete(User $user, Api $api): bool
    {
        return $user->id === $api->user_id;
    }
}
