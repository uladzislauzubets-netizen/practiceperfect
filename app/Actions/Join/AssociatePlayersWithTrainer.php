<?php

declare(strict_types=1);

namespace App\Actions\Join;

use App\Enums\ShareLinkType;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\ShareLinkUse;
use App\Models\User;
use App\Services\ContextResolver;
use App\Services\PlayerContext;
use App\Services\TrainerAssociations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssociatePlayersWithTrainer
{
    public function __construct(
        private readonly TrainerAssociations $associations,
        private readonly ContextResolver $contexts,
    ) {}

    /** @param  Collection<int, PlayerProfile>  $profiles */
    public function handle(ShareLink $link, User $actor, Collection $profiles): void
    {
        if ($link->type !== ShareLinkType::Player || ! $link->isUsable()) {
            throw ValidationException::withMessages(['link' => $link->state()->message()]);
        }

        if ($profiles->isEmpty()) {
            throw ValidationException::withMessages(['profiles' => 'Select at least one person.']);
        }

        DB::transaction(function () use ($link, $actor, $profiles) {
            foreach ($profiles as $profile) {
                $this->associations->attach($profile, $link->trainer, $link);

                ShareLinkUse::query()->create([
                    'share_link_id' => $link->id,
                    'user_id' => $actor->id,
                    'player_profile_id' => $profile->id,
                    'used_at' => now(),
                ]);
            }

            $link->increment('uses_count');
        });

        $this->contexts->forget($actor);
        $this->contexts->remember($actor, new PlayerContext($profiles->first(), $link->trainer));
    }
}
