<?php

declare(strict_types=1);

namespace App\Actions\Trainer;

use App\Enums\ShareLinkType;
use App\Models\ShareLink;
use App\Models\User;

final class GetPlayerShareLink
{
    public function handle(User $trainer): ShareLink
    {
        return ShareLink::query()->firstOrCreate(
            ['trainer_id' => $trainer->id, 'type' => ShareLinkType::Player, 'is_active' => true],
            ['code' => ShareLink::newCode(), 'created_by' => $trainer->id],
        );
    }
}
