<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainer;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\UpdatePlayerSkillRequest;
use App\Models\AvailabilitySlot;
use App\Models\PlayerProfile;
use App\Services\Availability\AvailabilityInsights;
use App\Services\TrainerAssociations;
use App\Support\AvailabilitySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(Request $request, AvailabilityInsights $insights): Response
    {
        $trainer = $request->user();

        $filters = $request->validate([
            'day' => ['nullable', 'integer', 'between:1,7'],
            'from' => ['nullable', 'date_format:H:i', 'required_with:day'],
            'to' => ['nullable', 'date_format:H:i', 'after:from'],
        ]);

        $profiles = PlayerProfile::query()
            ->forTrainer($trainer->id)
            ->with(['user', 'parent'])
            ->with(['associations' => fn ($query) => $query->where('trainer_id', $trainer->id)->where('status', ActiveStatus::Active)])
            ->when(isset($filters['day']), fn ($query) => $query->whereIn('id', $insights->profileIdsAvailableAt(
                $trainer->id,
                (int) $filters['day'],
                $filters['from'],
                $filters['to'] ?? $filters['from'],
            )))
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $slots = AvailabilitySlot::query()
            ->where('subject_type', (new PlayerProfile)->getMorphClass())
            ->whereIn('subject_id', $profiles->pluck('id'))
            ->where('trainer_id', $trainer->id)
            ->get()
            ->groupBy('subject_id');

        return Inertia::render('Trainer/Players/Index', [
            'players' => $profiles->map(function (PlayerProfile $profile) use ($slots) {
                $contact = $profile->ownerAccount();

                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'age' => $profile->age,
                    'gender' => $profile->gender?->value,
                    'skill_level' => $profile->skill_level,
                    'is_child' => $profile->is_child,
                    'contact_name' => $contact?->name,
                    'contact_email' => $contact?->email,
                    'contact_phone' => $contact?->phone,
                    'connected_at' => $profile->associations->first()?->connected_at->toIso8601String(),
                    'best_times' => AvailabilitySummary::format($slots->get($profile->id, collect())),
                ];
            })->all(),
            'filters' => ['day' => $filters['day'] ?? null, 'from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? ''],
            'best_windows' => $insights->bestWindows($trainer->id)->map(fn (array $window) => [
                ...$window,
                'label' => AvailabilitySummary::dayName($window['day_of_week']).' '.AvailabilitySummary::range($window['start'], $window['end']),
            ])->all(),
        ]);
    }

    public function updateSkill(UpdatePlayerSkillRequest $request, PlayerProfile $profile): RedirectResponse
    {
        $profile->update(['skill_level' => $request->validated('skill_level')]);

        return back()->with('success', 'Skill level updated.');
    }

    public function destroy(Request $request, PlayerProfile $profile, TrainerAssociations $associations): RedirectResponse
    {
        Gate::authorize('administerAsTrainer', $profile);

        $associations->detach($profile, $request->user());

        return back()->with('success', "{$profile->name} was removed from your roster.");
    }
}
