<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $target->status !== UserStatus::Deleted;
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->isNot($target) && $target->status === UserStatus::Active;
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $target->status === UserStatus::Inactive;
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->isNot($target) && $target->status !== UserStatus::Deleted;
    }

    public function impersonate(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin()
            && $actor->isNot($target)
            && ! $target->isSuperAdmin()
            && $target->status === UserStatus::Active;
    }
}
