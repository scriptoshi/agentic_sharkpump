<?php

namespace App\Policies;

use App\Models\ApiAuth;
use App\Models\User;

class ApiAuthPolicy
{

    public function view(User $user, ApiAuth $apiAuth): bool
    {
        return $user->id === $apiAuth->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ApiAuth $apiAuth): bool
    {
        return $user->id === $apiAuth->user_id;
    }

    public function delete(User $user, ApiAuth $apiAuth): bool
    {
        return $user->id === $apiAuth->user_id;
    }
}
