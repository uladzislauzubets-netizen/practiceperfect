<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlayerProfile;
use App\Models\User;

final readonly class PlayerContext
{
    public function __construct(
        public PlayerProfile $profile,
        public User $trainer,
    ) {}

    public function trainerName(): string
    {
        return $this->trainer->trainerProfile?->business_name ?? $this->trainer->name;
    }

    public function key(): string
    {
        return $this->profile->id.':'.$this->trainer->id;
    }

    public function label(User $viewer): string
    {
        if ($viewer->isChildAccount()) {
            return $this->trainerName();
        }

        $owner = $this->profile->user_id === $viewer->id ? $this->profile->first_name.' (Me)' : $this->profile->first_name;

        return $owner.' → '.$this->trainerName();
    }

    /** @return array{profile_id: int, trainer_id: int} */
    public function toStored(): array
    {
        return ['profile_id' => $this->profile->id, 'trainer_id' => $this->trainer->id];
    }
}
