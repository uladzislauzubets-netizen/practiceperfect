<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AvailabilitySlot;
use Illuminate\Support\Collection;

final class AvailabilitySummary
{
    private const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    public static function dayName(int $day): string
    {
        return self::DAYS[$day];
    }

    /** @param  Collection<int, AvailabilitySlot>  $slots */
    public static function format(Collection $slots): string
    {
        return $slots
            ->where('is_available', true)
            ->sortBy([['day_of_week', 'asc'], ['start_time', 'asc']])
            ->map(fn (AvailabilitySlot $slot) => self::DAYS[$slot->day_of_week].' '.self::range($slot->start_time, $slot->end_time))
            ->implode(', ');
    }

    public static function range(string $start, string $end): string
    {
        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));
        $sameMeridiem = ($startHour < 12) === ($endHour < 12);

        $from = self::time($startHour, $startMinute, ! $sameMeridiem);

        return $from.'-'.self::time($endHour, $endMinute, true);
    }

    private static function time(int $hour, int $minute, bool $withMeridiem): string
    {
        $display = $hour % 12 === 0 ? 12 : $hour % 12;
        $text = $minute === 0 ? (string) $display : sprintf('%d:%02d', $display, $minute);

        return $withMeridiem ? $text.($hour < 12 ? 'am' : 'pm') : $text;
    }
}
