<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final class PlayerRemovedFromTrainer
{
    use Dispatchable;

    public function __construct(
        public readonly PlayerProfile $profile,
        public readonly User $trainer,
    ) {}
}
