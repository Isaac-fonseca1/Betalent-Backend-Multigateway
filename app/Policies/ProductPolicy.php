<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Enums\UserRole;

class ProductPolicy
{
    /**
     * README: ADMIN - faz tudo | MANAGER - pode gerenciar produtos | FINANCE - pode gerenciar produtos
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can list products
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }

    public function update(User $user, Product $product): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }

    public function delete(User $user, Product $product): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }

    public function restore(User $user, Product $product): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE]);
    }
}
