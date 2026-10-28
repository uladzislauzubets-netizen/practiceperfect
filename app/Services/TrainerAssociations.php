<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Events\PlayerRemovedFromTrainer;
use App\Models\AvailabilitySlot;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\TrainerPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TrainerAssociations
{
    public function attach(PlayerProfile $profile, User $trainer, ?ShareLink $link = null): TrainerPlayer
    {
        $association = TrainerPlayer::query()->firstOrNew([
            'trainer_id' => $trainer->id,
            'player_profile_id' => $profile->id,
        ]);

        if ($association->exists && $association->status === ActiveStatus::Active) {
            return $association;
        }

        $association->fill([
            'status' => ActiveStatus::Active,
            'connected_at' => now(),
            'disconnected_at' => null,
            'share_link_id' => $link?->id,
        ])->save();

        return $association;
    }

    public function detach(PlayerProfile $profile, User $trainer): bool
    {
        $association = TrainerPlayer::query()
            ->where('trainer_id', $trainer->id)
            ->where('player_profile_id', $profile->id)
            ->where('status', ActiveStatus::Active)
            ->first();

        if ($association === null) {
            return false;
        }

        DB::transaction(function () use ($association, $profile, $trainer) {
            $association->update(['status' => ActiveStatus::Inactive, 'disconnected_at' => now()]);

            AvailabilitySlot::query()
                ->where('subject_type', $profile->getMorphClass())
                ->where('subject_id', $profile->id)
                ->where('trainer_id', $trainer->id)
                ->delete();
        });

        PlayerRemovedFromTrainer::dispatch($profile, $trainer);

        return true;
    }
}
