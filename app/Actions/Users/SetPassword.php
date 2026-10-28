<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

final class SetPassword
{
    public function handle(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'must_change_password' => false,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
