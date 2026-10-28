<?php

declare(strict_types=1);

namespace App\Http\Controllers\Coach;

use App\Actions\Availability\SaveAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\SaveAvailabilityRequest;
use App\Models\AvailabilitySlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTimesController extends Controller
{
    public function edit(Request $request): Response
    {
        $slots = $request->user()->availabilitySlots()
            ->orderBy('day_of_week')->orderBy('start_time')
            ->get()
            ->map(fn (AvailabilitySlot $slot) => [
                'day_of_week' => $slot->day_of_week,
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
                'is_available' => $slot->is_available,
            ])
            ->all();

        return Inertia::render('Availability/CoachTimes', ['slots' => $slots]);
    }

    public function update(SaveAvailabilityRequest $request, SaveAvailability $save): RedirectResponse
    {
        $save->handle($request->user(), null, $request->slots());

        return back()->with('success', 'Your times were saved.');
    }
}
