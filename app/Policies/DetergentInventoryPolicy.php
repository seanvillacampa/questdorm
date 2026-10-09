<?php

namespace App\Policies;

use App\Models\DetergentInventory;
use App\Models\DetergentLog;
use App\Models\User;

class DetergentInventoryPolicy
{
    /** Both owner and staff can view the inventory page. */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isFrontDesk();
    }

    /** Both owner and staff can add stock (restock). */
    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isFrontDesk();
    }

    /** Only the owner can delete restock log entries. */
    public function delete(User $user): bool
    {
        return $user->isOwner();
    }
}
