<?php

namespace App\Policies;

use App\Models\ApiTool;
use App\Models\User;

class ApiToolPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, ApiTool $apiTool): bool
    {
        return $user->id === $apiTool->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ApiTool $apiTool): bool
    {
        return $user->id === $apiTool->user_id;
    }

    public function delete(User $user, ApiTool $apiTool): bool
    {
        return $user->id === $apiTool->user_id;
    }
}
