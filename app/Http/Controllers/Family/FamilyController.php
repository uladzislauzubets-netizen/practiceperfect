<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\Family\CreateSelfPlayerProfile;
use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreSelfProfileRequest;
use App\Models\PlayerProfile;
use App\Models\TrainerPlayer;
use App\Models\User;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $profiles = PlayerProfile::query()
            ->with(['associations' => fn ($query) => $query->where('status', ActiveStatus::Active)->with('trainer.trainerProfile'), 'user'])
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('parent_id', $user->id))
            ->orderBy('is_child')->orderBy('first_name')
            ->get();

        $trainers = User::query()
            ->with('trainerProfile')
            ->whereIn('id', $user->trainerIds())
            ->get()
            ->map(fn (User $trainer) => ['id' => $trainer->id, 'name' => $trainer->trainerProfile?->business_name ?? $trainer->name])
            ->values()
            ->all();

        return Inertia::render('Family/Index', [
            'profiles' => $profiles->map(fn (PlayerProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'birth_date' => $profile->birth_date?->toDateString(),
                'age' => $profile->age,
                'gender' => $profile->gender?->value,
                'school' => $profile->school,
                'jersey_number' => $profile->jersey_number,
                'is_child' => $profile->is_child,
                'is_self' => $profile->user_id === $user->id,
                'avatar_url' => Media::photoUrl($profile->photo_thumb_path),
                'has_login' => $profile->is_child && $profile->user_id !== null,
                'login_email' => $profile->is_child ? $profile->user?->email : null,
                'allow_token_spending_without_approval' => $profile->allow_token_spending_without_approval,
                'trainers' => $profile->associations->map(fn (TrainerPlayer $association) => [
                    'id' => $association->trainer_id,
                    'name' => $association->trainer->trainerProfile?->business_name ?? $association->trainer->name,
                    'connected_at' => $association->connected_at->toIso8601String(),
                ])->values()->all(),
            ])->values()->all(),
            'trainers' => $trainers,
            'has_self_profile' => $profiles->contains(fn (PlayerProfile $profile) => $profile->user_id === $user->id),
        ]);
    }

    public function storeSelf(StoreSelfProfileRequest $request, CreateSelfPlayerProfile $create): RedirectResponse
    {
        $create->handle($request->user(), $request->safe()->only(['gender', 'birth_date']));

        return back()->with('success', 'You can now train yourself. Use a trainer\'s ShareLink to connect.');
    }
}
