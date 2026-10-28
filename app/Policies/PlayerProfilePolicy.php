<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActiveStatus;
use App\Models\PlayerProfile;
use App\Models\User;

class PlayerProfilePolicy
{
    public function manage(User $user, PlayerProfile $profile): bool
    {
        if (! $user->isPlayer() || $user->isChildAccount()) {
            return false;
        }

        return $profile->parent_id === $user->id || $profile->user_id === $user->id;
    }

    public function view(User $user, PlayerProfile $profile): bool
    {
        return $this->manage($user, $profile) || ($user->isChildAccount() && $profile->user_id === $user->id);
    }

    public function setAvailability(User $user, PlayerProfile $profile): bool
    {
        return $this->view($user, $profile);
    }

    public function administerAsTrainer(User $user, PlayerProfile $profile): bool
    {
        return $user->isTrainer() && $profile->associations()
            ->where('trainer_id', $user->id)
            ->where('status', ActiveStatus::Active)
            ->exists();
    }
}
