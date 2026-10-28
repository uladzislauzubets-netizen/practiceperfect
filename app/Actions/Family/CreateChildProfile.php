<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\ImageProcessor;
use App\Services\TrainerAssociations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class CreateChildProfile
{
    public function __construct(
        private readonly ImageProcessor $images,
        private readonly TrainerAssociations $associations,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, birth_date: string, gender: string, school?: ?string}  $data
     * @param  array<int, int>  $trainerIds
     */
    public function handle(User $parent, array $data, array $trainerIds, ?UploadedFile $photo = null): PlayerProfile
    {
        $stored = $photo === null ? null : $this->images->storePhoto($photo);

        return DB::transaction(function () use ($parent, $data, $trainerIds, $stored) {
            $profile = PlayerProfile::query()->create([
                'parent_id' => $parent->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'school' => $data['school'] ?? null,
                'is_child' => true,
                'photo_path' => $stored['path'] ?? null,
                'photo_thumb_path' => $stored['thumb'] ?? null,
            ]);

            foreach (User::query()->whereIn('id', $trainerIds)->get() as $trainer) {
                $this->associations->attach($profile, $trainer);
            }

            return $profile;
        });
    }
}
