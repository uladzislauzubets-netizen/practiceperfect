<?php

declare(strict_types=1);

namespace App\Services\Availability;

use App\Models\AvailabilitySlot;
use App\Models\User;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class CoachAvailabilityChecker
{
    public function isAvailable(User $coach, CarbonInterface $start, CarbonInterface $end): bool
    {
        if (! $start->isSameDay($end) || $end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('The range must start and end on the same day, with the end after the start.');
        }

        return AvailabilitySlot::query()
            ->where('subject_type', $coach->getMorphClass())
            ->where('subject_id', $coach->id)
            ->where('day_of_week', $start->dayOfWeekIso)
            ->where('is_available', true)
            ->where('start_time', '<=', $start->format('H:i:s'))
            ->where('end_time', '>=', $end->format('H:i:s'))
            ->exists();
    }
}
