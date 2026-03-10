<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Enums\UserRole;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }
    public function view(User $user, Client $client): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }
    public function update(User $user, Client $client): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }
    public function delete(User $user, Client $client): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
