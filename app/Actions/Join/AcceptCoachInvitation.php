<?php

declare(strict_types=1);

namespace App\Actions\Join;

use App\Enums\ActiveStatus;
use App\Enums\ShareLinkType;
use App\Models\CoachProfile;
use App\Models\ShareLink;
use App\Models\ShareLinkUse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptCoachInvitation
{
    public function handle(ShareLink $link, User $coach): void
    {
        DB::transaction(function () use ($link, $coach) {
            $fresh = ShareLink::query()->lockForUpdate()->findOrFail($link->id);

            if ($fresh->type !== ShareLinkType::Coach || ! $fresh->isUsable()) {
                throw ValidationException::withMessages(['link' => $fresh->state()->message()]);
            }

            if (! $coach->isCoach()) {
                throw ValidationException::withMessages(['link' => 'This invitation is for a coach account.']);
            }

            if ($fresh->target_email !== null && strcasecmp($fresh->target_email, $coach->email) !== 0) {
                throw ValidationException::withMessages(['link' => 'This invitation was sent to a different email address.']);
            }

            $existing = $coach->coachProfile;

            if ($existing !== null && $existing->status === ActiveStatus::Active && $existing->trainer_id !== $fresh->trainer_id) {
                throw ValidationException::withMessages(['link' => 'You are already an active coach for another trainer. Coaches can only work for one trainer at a time.']);
            }

            CoachProfile::query()->updateOrCreate(
                ['user_id' => $coach->id],
                ['trainer_id' => $fresh->trainer_id, 'status' => ActiveStatus::Active, 'joined_trainer_at' => now()],
            );

            $fresh->increment('uses_count');
            ShareLinkUse::query()->create(['share_link_id' => $fresh->id, 'user_id' => $coach->id, 'used_at' => now()]);
        });
    }
}
