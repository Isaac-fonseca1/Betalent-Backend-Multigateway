<?php

namespace App\Policies;

use App\Models\Gateway;
use App\Models\User;
use App\Enums\UserRole;

class GatewayPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }
    public function view(User $user, Gateway $gateway): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }
    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }
    public function update(User $user, Gateway $gateway): bool
    {
        return $user->role === UserRole::ADMIN;
    }
    public function delete(User $user, Gateway $gateway): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
