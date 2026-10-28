<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeactivateUser
{
    public function handle(User $admin, User $user): void
    {
        DB::transaction(function () use ($admin, $user) {
            $user->forceFill(['status' => UserStatus::Inactive, 'deactivated_at' => now(), 'remember_token' => null])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditLog::record($admin, 'user.deactivated', $user);
        });
    }
}
