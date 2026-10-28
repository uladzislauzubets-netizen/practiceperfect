<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\Family\AddChildToTrainer;
use App\Actions\Family\CreateChildLogin;
use App\Actions\Family\CreateChildProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\AddChildTrainerRequest;
use App\Http\Requests\Family\StoreChildLoginRequest;
use App\Http\Requests\Family\StoreChildRequest;
use App\Http\Requests\Family\UpdateChildRequest;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\ImageProcessor;
use App\Services\TrainerAssociations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request, CreateChildProfile $create): RedirectResponse
    {
        $profile = $create->handle(
            $request->user(),
            $request->safe()->only(['first_name', 'last_name', 'birth_date', 'gender', 'school']),
            $request->array('trainer_ids'),
            $request->file('photo'),
        );

        return back()->with('success', "{$profile->first_name} was added to your family.");
    }

    public function update(UpdateChildRequest $request, PlayerProfile $profile, ImageProcessor $images): RedirectResponse
    {
        abort_unless($profile->is_child, 404);

        $attributes = $request->safe()->only([
            'first_name', 'last_name', 'birth_date', 'gender', 'school', 'jersey_number', 'allow_token_spending_without_approval',
        ]);

        if ($request->hasFile('photo')) {
            $stored = $images->storePhoto($request->file('photo'));
            $images->delete($profile->photo_path, $profile->photo_thumb_path);
            $attributes = [...$attributes, 'photo_path' => $stored['path'], 'photo_thumb_path' => $stored['thumb']];
        }

        $profile->update($attributes);

        return back()->with('success', "{$profile->first_name}'s profile was saved.");
    }

    public function addTrainer(AddChildTrainerRequest $request, PlayerProfile $profile, AddChildToTrainer $add): RedirectResponse
    {
        $trainer = $add->handle($request->user(), $profile, $request->input('share_code'), $request->input('trainer_id'));

        return back()->with('success', "{$profile->first_name} now trains with ".($trainer->trainerProfile?->business_name ?? $trainer->name).'.');
    }

    public function removeTrainer(Request $request, PlayerProfile $profile, User $trainer, TrainerAssociations $associations): RedirectResponse
    {
        Gate::authorize('manage', $profile);

        abort_unless($associations->detach($profile, $trainer), 404);

        return back()->with('success', "{$profile->first_name} was removed from ".($trainer->trainerProfile?->business_name ?? $trainer->name).'.');
    }

    public function createLogin(StoreChildLoginRequest $request, PlayerProfile $profile, CreateChildLogin $create): RedirectResponse
    {
        abort_unless($profile->is_child, 404);

        $create->handle($request->user(), $profile, $request->safe()->only(['email', 'password']));

        return back()->with('success', "{$profile->first_name} can now sign in with their own login.");
    }
}
