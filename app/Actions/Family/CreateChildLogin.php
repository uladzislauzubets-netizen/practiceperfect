<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateChildLogin
{
    /** @param  array{email: string, password: string}  $data */
    public function handle(User $parent, PlayerProfile $child, array $data): User
    {
        if ($child->user_id !== null) {
            throw ValidationException::withMessages(['email' => 'This child already has a login.']);
        }

        return DB::transaction(function () use ($parent, $child, $data) {
            $account = User::query()->create([
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'email' => $data['email'],
                'phone' => $parent->phone,
                'password' => $data['password'],
                'role' => UserRole::Player,
                'status' => UserStatus::Active,
                'parent_id' => $parent->id,
                'email_verified_at' => now(),
                'photo_path' => $child->photo_path,
                'photo_thumb_path' => $child->photo_thumb_path,
            ]);

            $child->update(['user_id' => $account->id]);

            return $account;
        });
    }
}
