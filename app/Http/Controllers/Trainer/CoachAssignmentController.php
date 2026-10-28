<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainer;

use App\Actions\Availability\OverrideCoachAvailability;
use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\CheckCoachAvailabilityRequest;
use App\Http\Requests\Trainer\OverrideCoachAvailabilityRequest;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\Availability\CoachAvailabilityChecker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CoachAssignmentController extends Controller
{
    public function check(CheckCoachAvailabilityRequest $request, CoachAvailabilityChecker $checker): JsonResponse
    {
        $coach = $this->ownCoach($request->user(), $request->integer('coach_id'));
        $start = Carbon::parse($request->string('starts_at')->toString());
        $end = Carbon::parse($request->string('ends_at')->toString());

        if (! $start->isSameDay($end)) {
            return response()->json(['message' => 'The session must start and end on the same day.'], 422);
        }

        $available = $checker->isAvailable($coach, $start, $end);

        return response()->json([
            'available' => $available,
            'message' => $available
                ? null
                : "Coach {$coach->name} is not available at this time per their schedule. Continue anyway?",
        ]);
    }

    public function override(OverrideCoachAvailabilityRequest $request, OverrideCoachAvailability $override): JsonResponse
    {
        $coach = $this->ownCoach($request->user(), $request->integer('coach_id'));

        $record = $override->handle($request->user(), $coach, $request->integer('event_id'), $request->string('reason')->toString());

        return response()->json(['id' => $record->id], 201);
    }

    private function ownCoach(User $trainer, int $coachId): User
    {
        $profile = CoachProfile::query()
            ->with('user')
            ->where('user_id', $coachId)
            ->where('trainer_id', $trainer->id)
            ->where('status', ActiveStatus::Active)
            ->first();

        abort_if($profile === null, 404);

        return $profile->user;
    }
}
