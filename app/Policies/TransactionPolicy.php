<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Enums\UserRole;

class TransactionPolicy
{
    /**
     * All authenticated users can list and view transactions.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }

    /**
     * All authenticated users can create a purchase.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE, UserRole::USER]);
    }

    /**
     * README: ADMIN - faz tudo | FINANCE - pode realizar reembolso
     * MANAGER is NOT listed as having refund permission.
     */
    public function refund(User $user, Transaction $transaction): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }
}
