<?php

declare(strict_types=1);

namespace App\Actions\Availability;

use App\Models\AvailabilitySlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaveAvailability
{
    public const FULL_DAY_START = '00:00';

    public const FULL_DAY_END = '23:59';

    /**
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string, is_available: bool}>  $slots
     */
    public function handle(Model $subject, ?int $trainerId, array $slots): void
    {
        $this->assertNoOverlaps($slots);

        DB::transaction(function () use ($subject, $trainerId, $slots) {
            AvailabilitySlot::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->where('trainer_id', $trainerId)
                ->forceDelete();

            foreach ($slots as $slot) {
                $unavailable = ! $slot['is_available'];

                AvailabilitySlot::query()->create([
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'trainer_id' => $trainerId,
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => ($unavailable ? self::FULL_DAY_START : $slot['start_time']).':00',
                    'end_time' => ($unavailable ? self::FULL_DAY_END : $slot['end_time']).':00',
                    'is_available' => $slot['is_available'],
                ]);
            }
        });
    }

    /** @param  array<int, array{day_of_week: int, start_time: string, end_time: string, is_available: bool}>  $slots */
    private function assertNoOverlaps(array $slots): void
    {
        $byDay = collect($slots)->groupBy('day_of_week');

        foreach ($byDay as $daySlots) {
            if ($daySlots->contains(fn (array $slot) => ! $slot['is_available']) && $daySlots->count() > 1) {
                throw ValidationException::withMessages(['slots' => 'A day marked as not available cannot also have time ranges.']);
            }

            $ranges = $daySlots->where('is_available', true)->sortBy('start_time')->values();

            foreach ($ranges as $index => $range) {
                if ($range['end_time'] <= $range['start_time']) {
                    throw ValidationException::withMessages(['slots' => 'Each time range must end after it starts.']);
                }

                if ($index > 0 && $range['start_time'] < $ranges[$index - 1]['end_time']) {
                    throw ValidationException::withMessages(['slots' => 'Time ranges on the same day cannot overlap.']);
                }
            }
        }
    }
}
