<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;

final class ReactivateUser
{
    public function handle(User $admin, User $user): void
    {
        $user->forceFill(['status' => UserStatus::Active, 'deactivated_at' => null])->save();
        AuditLog::record($admin, 'user.reactivated', $user);
    }
}
