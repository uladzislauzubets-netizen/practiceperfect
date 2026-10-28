<?php

declare(strict_types=1);

namespace App\Actions\Availability;

use App\Enums\ActiveStatus;
use App\Models\CoachAvailabilityOverride;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class OverrideCoachAvailability
{
    public function handle(User $trainer, User $coach, int $eventId, string $reason): CoachAvailabilityOverride
    {
        $belongsToTrainer = $coach->coachProfile !== null
            && $coach->coachProfile->trainer_id === $trainer->id
            && $coach->coachProfile->status === ActiveStatus::Active;

        if (! $belongsToTrainer) {
            throw ValidationException::withMessages(['coach_id' => 'This coach is not part of your organization.']);
        }

        return CoachAvailabilityOverride::query()->create([
            'event_id' => $eventId,
            'coach_id' => $coach->id,
            'overridden_by' => $trainer->id,
            'reason' => $reason,
        ]);
    }
}
