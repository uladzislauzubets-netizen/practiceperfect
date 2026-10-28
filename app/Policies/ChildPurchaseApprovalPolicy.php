<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChildPurchaseApproval;
use App\Models\User;

class ChildPurchaseApprovalPolicy
{
    public function respond(User $user, ChildPurchaseApproval $approval): bool
    {
        return $user->isPlayer() && ! $user->isChildAccount() && $approval->parent_id === $user->id;
    }
}
