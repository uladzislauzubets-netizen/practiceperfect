<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateProfile
{
    private const COMMON = ['first_name', 'last_name', 'phone', 'school'];

    private const EMERGENCY = ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'];

    private const TRAINER = ['business_name', 'address', 'website', 'description'];

    private const COACH = ['bio', 'credentials', 'certifications', 'is_public'];

    private const PLAYER_PROFILE = ['school', 'jersey_number'];

    public function __construct(private readonly ImageProcessor $images) {}

    /** @param  array<string, mixed>  $data */
    public function handle(User $user, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): User
    {
        $stored = $photo === null ? null : $this->images->storePhoto($photo);
        $oldPaths = [$user->photo_path, $user->photo_thumb_path];

        DB::transaction(function () use ($user, $data, $stored, $removePhoto) {
            $attributes = $user->isChildAccount() ? [] : Arr::only($data, self::COMMON);

            if ($user->isPlayer() && ! $user->isChildAccount()) {
                $attributes = [...$attributes, ...Arr::only($data, self::EMERGENCY)];
                unset($attributes['school']);
            }

            if ($stored !== null) {
                $attributes = [...$attributes, 'photo_path' => $stored['path'], 'photo_thumb_path' => $stored['thumb'], 'photo_url' => null];
            } elseif ($removePhoto) {
                $attributes = [...$attributes, 'photo_path' => null, 'photo_thumb_path' => null, 'photo_url' => null];
            } elseif (! empty($data['photo_url']) && ! $user->isChildAccount()) {
                $attributes = [...$attributes, 'photo_url' => $data['photo_url'], 'photo_path' => null, 'photo_thumb_path' => null];
            }

            $user->fill($attributes)->save();

            if ($user->isTrainer()) {
                $user->trainerProfile?->fill(Arr::only($data, self::TRAINER))->save();
            }

            if ($user->isCoach()) {
                $user->coachProfile?->fill(Arr::only($data, self::COACH))->save();
            }

            if ($user->isPlayer() && $user->playerProfile !== null) {
                $profileData = Arr::only($data, self::PLAYER_PROFILE);

                if ($stored !== null) {
                    $profileData = [...$profileData, 'photo_path' => $stored['path'], 'photo_thumb_path' => $stored['thumb']];
                }

                $user->playerProfile->fill($profileData)->save();
            }
        });

        if ($stored !== null || $removePhoto || (! empty($data['photo_url']) && ! $user->isChildAccount())) {
            $this->images->delete(...array_diff($oldPaths, [$user->photo_path, $user->photo_thumb_path]));
        }

        return $user->refresh();
    }
}
