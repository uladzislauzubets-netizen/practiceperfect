<?php

declare(strict_types=1);

namespace App\Actions\Trainer;

use App\Enums\ActiveStatus;
use App\Enums\ShareLinkType;
use App\Models\ShareLink;
use App\Models\User;
use App\Notifications\CoachInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class InviteCoach
{
    public function handle(User $trainer, string $email, ?string $name = null, ?string $message = null): ShareLink
    {
        $email = strtolower(trim($email));
        $this->assertInvitable($trainer, $email);

        $link = DB::transaction(function () use ($trainer, $email, $name, $message) {
            ShareLink::query()
                ->where('trainer_id', $trainer->id)
                ->where('type', ShareLinkType::Coach)
                ->where('target_email', $email)
                ->where('uses_count', 0)
                ->update(['is_active' => false]);

            return ShareLink::query()->create([
                'code' => ShareLink::newCode(),
                'type' => ShareLinkType::Coach,
                'trainer_id' => $trainer->id,
                'created_by' => $trainer->id,
                'target_email' => $email,
                'invitee_name' => $name,
                'message' => $message,
                'expires_at' => now()->addDays(ShareLink::COACH_LINK_VALID_DAYS),
                'max_uses' => 1,
            ]);
        });

        Notification::route('mail', $email)->notify(new CoachInvitation(
            $trainer->trainerProfile?->business_name ?? $trainer->name,
            $link->url,
            $name,
            $message,
        ));

        return $link;
    }

    private function assertInvitable(User $trainer, string $email): void
    {
        $existing = User::query()->where('email', $email)->first();

        if ($existing === null) {
            return;
        }

        if (! $existing->isCoach()) {
            throw ValidationException::withMessages(['email' => 'This email belongs to an account that is not a coach.']);
        }

        $profile = $existing->coachProfile;

        if ($profile?->status === ActiveStatus::Active) {
            $message = $profile->trainer_id === $trainer->id
                ? 'This coach is already part of your organization.'
                : 'This coach is already active under another trainer.';

            throw ValidationException::withMessages(['email' => $message]);
        }
    }
}
