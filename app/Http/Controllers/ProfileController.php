<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Profile\UpdateProfile;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['trainerProfile', 'coachProfile', 'playerProfile']);
        $player = $user->playerProfile;

        return Inertia::render('Profile/Edit', [
            'limited' => $user->isChildAccount(),
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'school' => $user->isPlayer() ? $player?->school : $user->school,
                'photo_url' => $user->photo_url,
                'avatar_url' => $user->photo_thumb_path !== null ? Media::photoUrl($user->photo_thumb_path) : $user->photo_url,
                'email' => $user->email,
                'role_label' => $user->role->label(),
                'created_at' => $user->created_at->toIso8601String(),
                'business_name' => $user->trainerProfile?->business_name,
                'address' => $user->trainerProfile?->address,
                'website' => $user->trainerProfile?->website,
                'description' => $user->trainerProfile?->description,
                'bio' => $user->coachProfile?->bio,
                'credentials' => $user->coachProfile?->credentials,
                'certifications' => $user->coachProfile?->certifications,
                'is_public' => $user->coachProfile?->is_public ?? false,
                'jersey_number' => $player?->jersey_number,
                'skill_level' => $player?->skill_level,
                'has_player_profile' => $player !== null,
                'has_children' => $user->isPlayer() && $user->children()->exists(),
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'emergency_contact_relationship' => $user->emergency_contact_relationship,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle(
            $request->user(),
            $request->safe()->except(['photo', 'remove_photo']),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return back()->with('success', 'Your profile has been saved.');
    }
}
