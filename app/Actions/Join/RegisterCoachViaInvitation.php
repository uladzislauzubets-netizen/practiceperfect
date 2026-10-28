<?php

declare(strict_types=1);

namespace App\Actions\Join;

use App\Enums\ActiveStatus;
use App\Enums\ShareLinkType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\CoachProfile;
use App\Models\ShareLink;
use App\Models\ShareLinkUse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegisterCoachViaInvitation
{
    /** @param  array{first_name: string, last_name: string, email: string, password: string, phone: string}  $data */
    public function handle(ShareLink $link, array $data): User
    {
        return DB::transaction(function () use ($link, $data) {
            $fresh = ShareLink::query()->lockForUpdate()->findOrFail($link->id);

            if ($fresh->type !== ShareLinkType::Coach || ! $fresh->isUsable()) {
                throw ValidationException::withMessages(['link' => $fresh->state()->message()]);
            }

            if (strcasecmp($data['email'], (string) $fresh->target_email) !== 0) {
                throw ValidationException::withMessages(['email' => 'This invitation was sent to a different email address.']);
            }

            $coach = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::Coach,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            CoachProfile::query()->create([
                'user_id' => $coach->id,
                'trainer_id' => $fresh->trainer_id,
                'status' => ActiveStatus::Active,
                'joined_trainer_at' => now(),
            ]);

            $this->recordUse($fresh, $coach);

            return $coach;
        });
    }

    private function recordUse(ShareLink $link, User $coach): void
    {
        $link->increment('uses_count');
        ShareLinkUse::query()->create(['share_link_id' => $link->id, 'user_id' => $coach->id, 'used_at' => now()]);
    }
}
