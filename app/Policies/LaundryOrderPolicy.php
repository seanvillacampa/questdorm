<?php

namespace App\Policies;

use App\Models\LaundryOrder;
use App\Models\User;

class LaundryOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LaundryOrder $order): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isFrontDesk() || $user->isOwner();
    }

    public function update(User $user, LaundryOrder $order): bool
    {
        return $user->isFrontDesk() || $user->isOwner();
    }

    public function delete(User $user, LaundryOrder $order): bool
    {
        return $user->isOwner();
    }

    public function restore(User $user, LaundryOrder $order): bool
    {
        return $user->isOwner();
    }

    public function viewReports(User $user): bool
    {
        return $user->isOwner();
    }
}
