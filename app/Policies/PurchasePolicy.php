<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user)    {
        return $user->isAdmin() || $user->isUser();
    }

    public function view(User $user, Purchase $purchase)    {
        return $this->viewAny($user);
    }

    public function create(User $user)    {
        return $user->isAdmin();
    }

    public function update(User $user, Purchase $purchase)    {
        return $user->isAdmin();
    }

    public function delete(User $user, Purchase $purchase)    {
        return $user->isAdmin();
    }
}
