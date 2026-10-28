<?php

declare(strict_types=1);

namespace App\Services\Availability;

use App\Enums\ActiveStatus;
use App\Models\AvailabilitySlot;
use App\Models\PlayerProfile;
use App\Models\TrainerPlayer;
use Illuminate\Support\Collection;

final class AvailabilityInsights
{
    /** @return array<int, int> */
    public function profileIdsAvailableAt(int $trainerId, int $dayOfWeek, string $from, string $to): array
    {
        return AvailabilitySlot::query()
            ->where('subject_type', (new PlayerProfile)->getMorphClass())
            ->where('trainer_id', $trainerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->where('start_time', '<=', $from.':00')
            ->where('end_time', '>=', $to.':00')
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{day_of_week: int, start: string, end: string, players: int}>
     */
    public function bestWindows(int $trainerId, int $limit = 5): Collection
    {
        $activeProfileIds = TrainerPlayer::query()
            ->where('trainer_id', $trainerId)
            ->where('status', ActiveStatus::Active)
            ->pluck('player_profile_id');

        $slots = AvailabilitySlot::query()
            ->where('subject_type', (new PlayerProfile)->getMorphClass())
            ->where('trainer_id', $trainerId)
            ->where('is_available', true)
            ->whereIn('subject_id', $activeProfileIds)
            ->get();

        $windows = collect();

        foreach ($slots->groupBy('day_of_week') as $day => $daySlots) {
            $counts = [];

            for ($hour = 0; $hour < 24; $hour++) {
                $start = sprintf('%02d:00', $hour);
                $end = $hour === 23 ? '23:59' : sprintf('%02d:00', $hour + 1);
                $counts[$hour] = $daySlots
                    ->filter(fn (AvailabilitySlot $slot) => $slot->coversRange($start, $end))
                    ->pluck('subject_id')
                    ->unique()
                    ->count();
            }

            $hour = 0;
            while ($hour < 24) {
                if ($counts[$hour] === 0) {
                    $hour++;

                    continue;
                }

                $first = $hour;
                while ($hour + 1 < 24 && $counts[$hour + 1] === $counts[$first]) {
                    $hour++;
                }

                $windows->push([
                    'day_of_week' => (int) $day,
                    'start' => sprintf('%02d:00', $first),
                    'end' => $hour === 23 ? '24:00' : sprintf('%02d:00', $hour + 1),
                    'players' => $counts[$first],
                ]);
                $hour++;
            }
        }

        return $windows
            ->sortBy([['players', 'desc'], ['day_of_week', 'asc'], ['start', 'asc']])
            ->take($limit)
            ->values();
    }
}
