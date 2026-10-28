<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CreateSelfPlayerProfile
{
    /** @param  array{gender?: ?string, birth_date?: ?string}  $data */
    public function handle(User $user, array $data): PlayerProfile
    {
        if ($user->playerProfile !== null) {
            throw ValidationException::withMessages(['profile' => 'You already have a player profile.']);
        }

        return PlayerProfile::query()->create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'is_child' => false,
        ]);
    }
}
