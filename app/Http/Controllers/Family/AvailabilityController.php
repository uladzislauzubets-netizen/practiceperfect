<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\Availability\SaveAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\SaveAvailabilityRequest;
use App\Models\AvailabilitySlot;
use App\Services\ContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function edit(Request $request, ContextResolver $contexts): Response
    {
        $context = $contexts->current($request->user());

        if ($context === null) {
            return Inertia::render('Availability/Player', ['context' => null, 'slots' => []]);
        }

        Gate::authorize('setAvailability', $context->profile);

        $slots = $context->profile->availabilitySlots()
            ->where('trainer_id', $context->trainer->id)
            ->orderBy('day_of_week')->orderBy('start_time')
            ->get()
            ->map(fn (AvailabilitySlot $slot) => [
                'day_of_week' => $slot->day_of_week,
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
                'is_available' => $slot->is_available,
            ])
            ->all();

        return Inertia::render('Availability/Player', [
            'context' => ['player' => $context->profile->name, 'trainer' => $context->trainerName()],
            'slots' => $slots,
        ]);
    }

    public function update(SaveAvailabilityRequest $request, ContextResolver $contexts, SaveAvailability $save): RedirectResponse
    {
        $context = $contexts->current($request->user());

        abort_if($context === null, 404);
        Gate::authorize('setAvailability', $context->profile);

        $save->handle($context->profile, $context->trainer->id, $request->slots());

        return back()->with('success', 'Availability saved. Trainers can see these preferences when planning sessions.');
    }
}
